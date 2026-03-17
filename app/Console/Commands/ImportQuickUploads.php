<?php

namespace App\Console\Commands;

use App\Enums\QuickUploadStatus;
use App\Models\QuickUpload;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportQuickUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-quick-uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import quick uploads from paginated API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $baseUrl = 'https://diuqbank.com/public/submissions';
        $page = 1;

        $this->info("Starting data import from {$baseUrl}...");

        do {
            $this->info("Fetching page {$page}...");
            $response = Http::retry(3, 1000)->get($baseUrl, ['page' => $page]);

            if ($response->failed()) {
                $this->error("Failed to fetch page {$page}.");
                break;
            }

            $json = $response->json();
            $data = $json['data'] ?? [];

            if (empty($data)) {
                $this->info("No more data found on page {$page}.");
                break;
            }

            foreach ($data as $item) {
                // Ensure the user exists
                $userData = $item['user'] ?? null;
                if (! $userData || empty($userData['email'])) {
                    $this->warn("Skipping item {$item['id']} due to missing user data.");

                    continue;
                }

                $user = User::firstOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'] ?? 'Unknown',
                        'username' => $userData['username'] ?? Str::slug(($userData['name'] ?? 'user').'-'.Str::random(5)),
                        'password' => bcrypt(Str::random(16)),
                    ]
                );

                // Download the PDF
                $pdfUrl = $item['pdf_original_temporary_url'] ?? $item['pdf_url'] ?? null;

                if (! $pdfUrl) {
                    $this->warn("Skipping item {$item['id']} due to missing PDF url.");

                    continue;
                }

                $pdfContent = null;
                try {
                    $pdfResponse = Http::timeout(60)->retry(5, 2000)->get($pdfUrl);
                    if ($pdfResponse->successful()) {
                        $pdfContent = $pdfResponse->body();
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to download PDF for item {$item['id']}: {$e->getMessage()}");

                    continue;
                }

                if (! $pdfContent) {
                    $this->warn("Failed to retrieve PDF content for item {$item['id']}.");

                    continue;
                }

                $disk = 's3';
                // Get extension from url
                $extension = 'pdf';
                if (! empty($item['media'][0]['file_name'])) {
                    $extension = pathinfo($item['media'][0]['file_name'], PATHINFO_EXTENSION) ?: 'pdf';
                }

                $filename = 'quick-uploads/'.Str::uuid().'.'.$extension;
                Storage::disk($disk)->put($filename, $pdfContent);

                // Create the QuickUpload record
                QuickUpload::create([
                    'user_id' => $user->id,
                    'pdf_path' => $filename,
                    'pdf_size' => strlen($pdfContent),
                    'status' => QuickUploadStatus::Pending,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]);

                $this->info("Successfully imported submission ID {$item['id']} as QuickUpload.");
            }

            // Move to next page if it exists
            $nextUrl = $json['links']['next'] ?? null;
            if ($nextUrl) {
                $page++;
            }

        } while ($nextUrl);

        $this->info('Data import completed successfully.');
    }
}
