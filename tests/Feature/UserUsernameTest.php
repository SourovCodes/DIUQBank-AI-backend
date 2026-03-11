<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('users require an explicit username', function () {
    $user = User::factory()->make([
        'username' => null,
    ]);

    expect(fn () => $user->save())->toThrow(QueryException::class);
});

test('users persist the provided username', function () {
    $user = User::factory()->create([
        'username' => 'taylor_otwell',
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'username' => 'taylor_otwell',
    ]);
});
