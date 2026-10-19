<?php

use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('user serialization hides authentication secrets', function () {
    $user = new User;
    $user->setRawAttributes([
        'name' => 'Customer', 'password' => 'secret-hash', 'remember_token' => 'secret-token',
        'email_verified_at' => '2026-01-01 12:00:00',
    ]);
    expect($user->toArray())->toHaveKey('name')->not->toHaveKeys(['password', 'remember_token'])
        ->and($user->email_verified_at)->toBeInstanceOf(Carbon::class);
});

test('user roles cannot be mass assigned', function () {
    expect((new User)->isFillable('role'))->toBeFalse();
});
