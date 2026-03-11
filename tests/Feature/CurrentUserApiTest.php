<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('current user endpoint requires authentication', function () {
    $this->getJson('/api/v1/auth/user')->assertUnauthorized();
});

test('current user endpoint returns the authenticated user resource', function () {
    $user = User::factory()->create([
        'name' => 'Sourov Hossain',
        'username' => 'sourov',
        'email' => 'sourov@example.com',
        'avatar' => 'avatars/sourov.png',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/auth/user');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', 'Sourov Hossain')
        ->assertJsonPath('data.username', 'sourov')
        ->assertJsonPath('data.email', 'sourov@example.com')
        ->assertJsonPath('data.avatar', 'avatars/sourov.png')
        ->assertJsonMissingPath('data.password')
        ->assertJsonMissingPath('data.remember_token');
});
