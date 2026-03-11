<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

it('creates a sanctum token for a google user', function () {
    $provider = \Mockery::mock();

    $provider->shouldReceive('stateless')->once()->andReturnSelf();
    $provider->shouldReceive('userFromToken')->once()->with('valid-google-id-token')->andReturn(
        (new SocialiteUser)->map([
            'sub' => 'google-user-123',
            'name' => 'Taylor Otwell',
            'email' => 'taylor.otwell@example.com',
            'avatar' => 'https://example.com/avatars/taylor.png',
        ])
    );

    Socialite::shouldReceive('buildProvider')->once()->andReturn($provider);

    $response = $this->postJson('/api/v1/auth/google', [
        'token' => 'valid-google-id-token',
        'token_name' => 'nextjs-web',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'taylor.otwell@example.com')
        ->assertJsonPath('data.user.name', 'Taylor Otwell')
        ->assertJsonPath('data.user.username', 'taylor_otwell')
        ->assertJsonPath('data.user.avatar', 'https://example.com/avatars/taylor.png');

    $user = User::query()->firstWhere('email', 'taylor.otwell@example.com');

    expect($user)->not->toBeNull();
    expect($user->avatar)->toBe('https://example.com/avatars/taylor.png');
    expect($user->tokens()->count())->toBe(1);
});

it('reuses an existing user for google auth', function () {
    $user = User::factory()->create([
        'email' => 'sourov@example.com',
        'username' => 'sourov',
        'name' => 'Old Name',
        'avatar' => 'https://example.com/avatars/old.png',
    ]);

    $provider = \Mockery::mock();

    $provider->shouldReceive('stateless')->once()->andReturnSelf();
    $provider->shouldReceive('userFromToken')->once()->with('existing-google-id-token')->andReturn(
        (new SocialiteUser)->map([
            'sub' => 'google-user-456',
            'name' => 'Sourov Hossain',
            'email' => 'sourov@example.com',
            'avatar' => 'https://example.com/avatars/sourov.png',
        ])
    );

    Socialite::shouldReceive('buildProvider')->once()->andReturn($provider);

    $response = $this->postJson('/api/v1/auth/google', [
        'token' => 'existing-google-id-token',
        'token_name' => 'flutter-app',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.username', 'sourov')
        ->assertJsonPath('data.user.name', 'Sourov Hossain')
        ->assertJsonPath('data.user.avatar', 'https://example.com/avatars/sourov.png');

    expect(User::query()->count())->toBe(1);
    expect($user->fresh()->avatar)->toBe('https://example.com/avatars/sourov.png');
    expect($user->fresh()->tokens()->count())->toBe(1);
});

it('returns a validation error for an invalid google token', function () {
    $provider = \Mockery::mock();

    $provider->shouldReceive('stateless')->once()->andReturnSelf();
    $provider->shouldReceive('userFromToken')->once()->with('invalid-token')->andThrow(new Exception('Invalid token'));

    Socialite::shouldReceive('buildProvider')->once()->andReturn($provider);

    $this->postJson('/api/v1/auth/google', [
        'token' => 'invalid-token',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

it('logs out by revoking only the current sanctum token', function () {
    $user = User::factory()->create();

    $currentToken = $user->createToken('nextjs-web');
    $otherToken = $user->createToken('flutter-app');

    $this->withToken($currentToken->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJson([
            'message' => 'Logged out successfully.',
        ]);

    expect($user->fresh()->tokens()->pluck('name')->all())->toBe([$otherToken->accessToken->name]);
});
