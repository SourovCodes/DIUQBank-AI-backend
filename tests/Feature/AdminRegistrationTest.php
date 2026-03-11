<?php

use App\Filament\Pages\Auth\Register as RegisterPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin registration page shows a required username field', function () {
    $response = $this->get('/admin/register');

    $response
        ->assertSuccessful()
        ->assertSee('Username')
        ->assertDontSee("We'll generate one from your email if you leave this blank.");
});

test('admin registration requires a username', function () {
    Livewire::test(RegisterPage::class)
        ->set('data.name', 'Admin User')
        ->set('data.email', 'admin@example.com')
        ->set('data.password', 'password')
        ->set('data.passwordConfirmation', 'password')
        ->call('register')
        ->assertHasErrors(['data.username' => ['required']]);
});

test('provided usernames are preserved when creating users', function () {
    $user = User::factory()->make([
        'email' => 'admin@example.com',
        'username' => 'custom_admin',
    ]);

    $user->save();

    expect($user->username)->toBe('custom_admin');
});
