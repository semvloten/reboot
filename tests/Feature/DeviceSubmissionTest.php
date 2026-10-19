<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->customer = User::factory()->create();
    $this->submission = [
        'type' => 'telefoon', 'brand' => 'Samsung', 'model' => 'Galaxy',
        'serial_number' => ' reboot-123 ', 'condition' => 'goed',
        'accessories' => 'Charger', 'asking_price' => '150,25',
    ];
});

test('customers can open the submission form', function () {
    $this->actingAs($this->customer)->get(route('devices.create'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('devices/create'));
});

test('submission normalizes input and protects ownership and status', function () {
    $this->actingAs($this->customer)->post(route('devices.store'), [
        ...$this->submission, 'user_id' => User::factory()->create()->id,
        'status' => 'goedgekeurd', 'priority' => 2,
        'photos' => [UploadedFile::fake()->image('phone.jpg')],
    ])->assertRedirect(route('shop.index'))->assertSessionHasNoErrors();
    $device = Device::sole();
    expect($device->user_id)->toBe($this->customer->id)
        ->and($device->serial_number)->toBe('REBOOT-123')
        ->and($device->asking_price)->toBe('150.25')
        ->and($device->status)->toBe('in behandeling')
        ->and($device->priority)->toBe(1)->and($device->photos)->toHaveCount(1);
    Storage::disk('local')->assertExists($device->photos[0]);
});

test('submission works without optional fields', function () {
    unset($this->submission['accessories']);
    $this->actingAs($this->customer)->post(route('devices.store'), $this->submission)->assertSessionHasNoErrors();
    expect(Device::sole()->photos)->toBe([]);
});

test('failed database saves remove uploaded photos and preserve the error', function () {
    Device::creating(function (): void {
        throw new RuntimeException('Database unavailable');
    });
    $this->withoutExceptionHandling();

    try {
        $this->actingAs($this->customer)->post(route('devices.store'), [
            ...$this->submission, 'photos' => [UploadedFile::fake()->image('phone.jpg')],
        ]);
        $this->fail('The storage error should propagate.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Database unavailable');
    }

    $this->assertDatabaseCount('devices', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('invalid submissions do not create devices', function (string $field, mixed $value) {
    $this->actingAs($this->customer)->post(route('devices.store'), [
        ...$this->submission, $field => $value,
    ])->assertSessionHasErrors($field);
    $this->assertDatabaseCount('devices', 0);
})->with([
    ['type', 'tablet'], ['brand', ''], ['brand', str_repeat('a', 101)],
    ['model', ''], ['serial_number', ' '], ['serial_number', ['invalid']],
    ['condition', 'broken'], ['accessories', str_repeat('a', 2001)],
    ['asking_price', 0], ['asking_price', -1], ['asking_price', '1.234'],
    ['asking_price', 'abc'], ['asking_price', '100000000'],
]);

test('invalid photo uploads are rejected', function (string $kind) {
    $photos = match ($kind) {
        'document' => [UploadedFile::fake()->create('secret.pdf', 20, 'application/pdf')],
        'large' => [UploadedFile::fake()->image('phone.jpg')->size(2049)],
        'many' => array_map(fn () => UploadedFile::fake()->image('phone.jpg'), range(1, 6)),
    };
    $this->actingAs($this->customer)->post(route('devices.store'), [
        ...$this->submission, 'photos' => $photos,
    ])->assertSessionHasErrors();
    $this->assertDatabaseCount('devices', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with(['document', 'large', 'many']);

test('active serial numbers cannot be reused', function (string $status) {
    Device::factory()->create(['serial_number' => 'REBOOT-123', 'status' => $status]);
    $this->actingAs($this->customer)->post(route('devices.store'), $this->submission)
        ->assertSessionHasErrors('serial_number');
    $this->assertDatabaseCount('devices', 1);
})->with(['in behandeling', 'goedgekeurd', 'onderhoud nodig', 'gereserveerd']);

test('rejected serial numbers may be submitted again', function () {
    Device::factory()->create(['serial_number' => 'REBOOT-123', 'status' => 'afgekeurd']);
    $this->actingAs($this->customer)->post(route('devices.store'), $this->submission)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('devices', 2);
});

test('customers see only their own devices and inspection notes', function () {
    $device = Device::factory()->for($this->customer)->create([
        'inspection' => ['notes' => 'Screen needs repair'], 'photos' => ['private.jpg'],
    ]);
    Device::factory()->create();
    $this->actingAs($this->customer)->get(route('devices.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('devices/index')
            ->has('devices', 1)->where('devices.0.id', $device->id)
            ->where('devices.0.inspection.notes', 'Screen needs repair')
            ->where('devices.0.photo_url', route('devices.photo', $device)));
});

test('inspectors cannot use customer device pages or submit devices', function () {
    $this->actingAs(User::factory()->create(['role' => 'inspector']));
    foreach (['devices.index', 'devices.create', 'devices.reservations'] as $name) {
        $this->get(route($name))->assertForbidden();
    }
    $this->post(route('devices.store'), $this->submission)->assertForbidden();
    $this->assertDatabaseCount('devices', 0);
});
