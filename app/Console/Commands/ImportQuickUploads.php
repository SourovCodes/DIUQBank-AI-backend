<?php

namespace App\Console\Commands;

use App\Enums\QuickUploadStatus;
use App\Jobs\CompressQuickUploadPdf;
use App\Models\QuickUpload;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImportQuickUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-quick-uploads {--concurrency=8 : Number of PDFs to download concurrently}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import quick uploads from paginated API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = 'https://diuqbank.com/public/submissions';
        $page = 1;
        $concurrency = max(1, (int) $this->option('concurrency'));

        $this->info("Starting data import from {$baseUrl}...");

        do {
            $this->info("Fetching page {$page}...");
            $response = Http::retry(3, 1000)->get($baseUrl, ['page' => $page]);

            if ($response->failed()) {
                $this->error("Failed to fetch page {$page}.");

                return self::FAILURE;
            }

            $json = $response->json();
            $data = $json['data'] ?? [];

            if (empty($data)) {
                $this->info("No more data found on page {$page}.");
                break;
            }

            $this->importPage($data, $concurrency);

            // Move to next page if it exists
            $nextUrl = $json['links']['next'] ?? null;
            if ($nextUrl) {
                $page++;
            }

        } while ($nextUrl);

        $this->info('Data import completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function importPage(array $items, int $concurrency): void
    {
        $preparedItems = $this->prepareItems($items);

        if ($preparedItems === []) {
            return;
        }

        $usersByEmail = $this->resolveUsersByEmail($preparedItems);
        $disk = Storage::disk('s3');
        $quickUploads = [];

        foreach (array_chunk($preparedItems, $concurrency) as $chunk) {
            $responses = $this->downloadChunk($chunk);

            foreach ($chunk as $item) {
                $response = $responses[(string) $item['source_id']] ?? null;

                if (! $response instanceof Response || $response->failed()) {
                    $this->warn("Failed to retrieve PDF content for item {$item['source_id']}.");

                    continue;
                }

                $pdfContent = $response->body();

                if ($pdfContent === '') {
                    $this->warn("Failed to retrieve PDF content for item {$item['source_id']}.");

                    continue;
                }

                $filename = 'quick-uploads/'.Str::uuid().'.'.$item['extension'];

                try {
                    $stored = $disk->put($filename, $pdfContent);
                } catch (Throwable $throwable) {
                    $this->error("Failed to store PDF for item {$item['source_id']}: {$throwable->getMessage()}");

                    continue;
                }

                if (! $stored) {
                    $this->error("Failed to store PDF for item {$item['source_id']}.");

                    continue;
                }

                /** @var User $user */
                $user = $usersByEmail[$item['email_key']];

                $quickUploads[] = [
                    'user_id' => $user->id,
                    'pdf_path' => $filename,
                    'pdf_size' => strlen($pdfContent),
                    'status' => QuickUploadStatus::Pending->value,
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];

                $this->info("Successfully imported submission ID {$item['source_id']} as QuickUpload.");
            }
        }

        if ($quickUploads !== []) {
            QuickUpload::query()->insert($quickUploads);

            QuickUpload::query()
                ->whereIn('pdf_path', array_column($quickUploads, 'pdf_path'))
                ->get()
                ->each(static function (QuickUpload $quickUpload): void {
                    CompressQuickUploadPdf::dispatch($quickUpload)->afterCommit();
                });
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function prepareItems(array $items): array
    {
        $preparedItems = [];

        foreach ($items as $item) {
            $itemId = (string) ($item['id'] ?? 'unknown');
            $userData = $item['user'] ?? null;

            if (! is_array($userData) || blank($userData['email'] ?? null)) {
                $this->warn("Skipping item {$itemId} due to missing user data.");

                continue;
            }

            $pdfUrl = $item['pdf_original_temporary_url'] ?? $item['pdf_url'] ?? null;

            if (blank($pdfUrl)) {
                $this->warn("Skipping item {$itemId} due to missing PDF url.");

                continue;
            }

            $email = trim((string) $userData['email']);
            $preferredUsername = trim((string) ($userData['username'] ?? ''));
            $fallbackName = trim((string) ($userData['name'] ?? 'Unknown'));

            $preparedItems[] = [
                'source_id' => $itemId,
                'email' => $email,
                'email_key' => Str::lower($email),
                'user_name' => $fallbackName !== '' ? $fallbackName : 'Unknown',
                'preferred_username' => $preferredUsername !== '' ? $preferredUsername : $fallbackName,
                'pdf_url' => (string) $pdfUrl,
                'extension' => $this->resolveExtension($item),
                'created_at' => $this->normalizeTimestamp($item['created_at'] ?? null),
                'updated_at' => $this->normalizeTimestamp($item['updated_at'] ?? null),
            ];
        }

        return $preparedItems;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, User>
     */
    protected function resolveUsersByEmail(array $items): array
    {
        $emails = array_values(array_unique(array_map(
            static fn (array $item): string => $item['email'],
            $items,
        )));

        $usersByEmail = User::query()
            ->whereIn('email', $emails)
            ->get()
            ->keyBy(static fn (User $user): string => Str::lower((string) $user->email))
            ->all();

        $missingUsers = [];

        foreach ($items as $item) {
            if (! isset($usersByEmail[$item['email_key']])) {
                $missingUsers[$item['email_key']] = $item;
            }
        }

        if ($missingUsers === []) {
            return $usersByEmail;
        }

        $preferredUsernames = array_values(array_unique(array_map(
            static fn (array $item): string => (string) $item['preferred_username'],
            array_values($missingUsers),
        )));

        $reservedUsernames = User::query()
            ->whereIn('username', $preferredUsernames)
            ->pluck('username')
            ->flip()
            ->map(static fn (): bool => true)
            ->all();

        foreach ($usersByEmail as $user) {
            $reservedUsernames[$user->username] = true;
        }

        $timestamp = now();
        $rows = [];

        foreach ($missingUsers as $item) {
            $rows[] = [
                'name' => $item['user_name'],
                'username' => $this->generateUniqueUsername((string) $item['preferred_username'], $reservedUsernames),
                'email' => $item['email'],
                'password' => Hash::make(Str::random(32)),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        User::query()->insert($rows);

        return User::query()
            ->whereIn('email', $emails)
            ->get()
            ->keyBy(static fn (User $user): string => Str::lower((string) $user->email))
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, Response|null>
     */
    protected function downloadChunk(array $items): array
    {
        try {
            /** @var array<string, Response> $responses */
            $responses = Http::pool(fn (Pool $pool): array => array_map(
                fn (array $item): mixed => $pool
                    ->as((string) $item['source_id'])
                    ->retry(5, 2000)
                    ->timeout(60)
                    ->get((string) $item['pdf_url']),
                $items,
            ));

            return $responses;
        } catch (Throwable $throwable) {
            $this->warn('Concurrent download failed for a batch, retrying that batch sequentially.');
        }

        $responses = [];

        foreach ($items as $item) {
            try {
                $responses[(string) $item['source_id']] = Http::retry(5, 2000)
                    ->timeout(60)
                    ->get((string) $item['pdf_url']);
            } catch (Throwable $throwable) {
                $this->error("Failed to download PDF for item {$item['source_id']}: {$throwable->getMessage()}");
                $responses[(string) $item['source_id']] = null;
            }
        }

        return $responses;
    }

    /**
     * @param  array<string, bool>  $reservedUsernames
     */
    protected function generateUniqueUsername(string $value, array &$reservedUsernames): string
    {
        $baseUsername = (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');

        if ($baseUsername === '') {
            $baseUsername = 'user';
        }

        $username = $baseUsername;
        $suffix = 1;

        while (isset($reservedUsernames[$username])) {
            $suffixText = '_'.$suffix;
            $maxBaseLength = 255 - strlen($suffixText);
            $username = substr($baseUsername, 0, $maxBaseLength).$suffixText;
            $suffix++;
        }

        $reservedUsernames[$username] = true;

        return $username;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolveExtension(array $item): string
    {
        $fileName = data_get($item, 'media.0.file_name');

        if (! is_string($fileName) || $fileName === '') {
            return 'pdf';
        }

        return pathinfo($fileName, PATHINFO_EXTENSION) ?: 'pdf';
    }

    protected function normalizeTimestamp(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->toDateTimeString();
        }

        return now()->toDateTimeString();
    }
}
