<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGoogleAuthRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Throwable;

class GoogleAuthController extends Controller
{
    public function store(StoreGoogleAuthRequest $request): JsonResponse
    {
        try {
            $googleUser = Socialite::buildProvider(GoogleProvider::class, config('services.google'))
                ->stateless()
                ->userFromToken($request->token());
        } catch (Throwable $throwable) {
            throw ValidationException::withMessages([
                'token' => 'The provided Google token is invalid.',
            ]);
        }

        $email = $googleUser->getEmail();

        if (! is_string($email) || $email === '') {
            throw ValidationException::withMessages([
                'token' => 'The Google account did not provide an email address.',
            ]);
        }

        $user = User::query()->firstOrNew([
            'email' => $email,
        ]);

        $user->name = $this->resolveName($googleUser, $email);
        $user->username ??= $this->generateUniqueUsername($email, $user->getKey());
        $user->email_verified_at ??= now();

        if (! $user->exists) {
            $user->password = Str::random(40);
        }

        $user->save();

        $token = $user->createToken($request->tokenName())->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    private function resolveName(SocialiteUser $googleUser, string $email): string
    {
        $name = trim((string) ($googleUser->getName() ?? ''));

        if ($name !== '') {
            return $name;
        }

        return Str::before($email, '@');
    }

    private function generateUniqueUsername(string $email, mixed $ignoreUserId = null): string
    {
        $baseUsername = (string) Str::of(Str::before($email, '@'))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');

        if ($baseUsername === '') {
            $baseUsername = 'user';
        }

        $username = $baseUsername;
        $suffix = 1;

        while ($this->usernameExists($username, $ignoreUserId)) {
            $suffixText = '_'.$suffix;
            $maxBaseLength = 255 - strlen($suffixText);
            $username = substr($baseUsername, 0, $maxBaseLength).$suffixText;
            $suffix++;
        }

        return $username;
    }

    private function usernameExists(string $username, mixed $ignoreUserId = null): bool
    {
        return User::query()
            ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->where('username', $username)
            ->exists();
    }
}
