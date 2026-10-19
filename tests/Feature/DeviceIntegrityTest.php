<?php

use App\Models\Device;
use Illuminate\Database\UniqueConstraintViolationException;

test('database rejects duplicate active serials independently of request validation', function () {
    Device::factory()->create(['serial_number' => 'REBOOT-123']);

    expect(fn () => Device::factory()->create(['serial_number' => ' reboot-123 ']))
        ->toThrow(UniqueConstraintViolationException::class);

    $this->assertDatabaseCount('devices', 1);
});

test('rejection frees a serial number and reactivation cannot duplicate it', function () {
    $device = Device::factory()->create(['serial_number' => 'REBOOT-123']);
    $device->status = 'afgekeurd';
    $device->save();
    Device::factory()->create(['serial_number' => 'REBOOT-123']);

    expect(function () use ($device) {
        $device->status = 'goedgekeurd';
        $device->save();
    })->toThrow(UniqueConstraintViolationException::class);

    expect($device->fresh()->status)->toBe('afgekeurd');
});
