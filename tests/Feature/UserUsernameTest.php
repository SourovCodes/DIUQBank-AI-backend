<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('users have a generated username', function () {
    $user = User::factory()->create();

    expect($user->username)->not->toBeEmpty();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'username' => $user->username,
    ]);
});
