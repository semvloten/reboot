<?php

use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('device casts preserve prices photos and inspection data', function () {
    $device = new Device;
    $device->forceFill([
        'asking_price' => '19.5', 'photos' => ['private.jpg'],
        'inspection' => ['works' => true], 'priority' => '2',
        'assigned_to_user_id' => '7', 'inspected_at' => '2026-01-01 12:00:00',
    ]);
    expect($device->asking_price)->toBe('19.50')->and($device->photos)->toBe(['private.jpg'])
        ->and($device->inspection)->toBe(['works' => true])->and($device->priority)->toBe(2)
        ->and($device->assigned_to_user_id)->toBe(7)->and($device->inspected_at)->toBeInstanceOf(Carbon::class);
});

test('device relations use the correct foreign keys', function () {
    $device = new Device;
    expect($device->user()->getForeignKeyName())->toBe('user_id')
        ->and($device->assignedInspector()->getForeignKeyName())->toBe('assigned_to_user_id')
        ->and($device->reservedBy()->getForeignKeyName())->toBe('reserved_by_user_id')
        ->and($device->reservedBy()->getRelated())->toBeInstanceOf(User::class);
});

test('device workflow fields are protected from mass assignment', function (string $field) {
    expect((new Device)->isFillable($field))->toBeFalse();
})->with(['user_id', 'status', 'priority', 'assigned_to_user_id', 'reserved_by_user_id', 'inspection', 'inspected_by_user_id', 'inspected_at']);
