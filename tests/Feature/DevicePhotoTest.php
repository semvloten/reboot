<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->customer = User::factory()->create(['role' => 'customer']);
    $this->actingAs($this->customer);
});

test('uploaded photos are saved privately with the device', function () {
    $this->post(route('devices.store'), [
        'type' => 'telefoon', 'brand' => 'Samsung', 'model' => 'Galaxy S23',
        'serial_number' => 'PHOTO-123', 'condition' => 'goed', 'asking_price' => 150,
        'photos' => [UploadedFile::fake()->image('phone.jpg')],
    ])->assertSessionHasNoErrors()->assertRedirect(route('devices.create'));
    $device = Device::query()->sole();
    expect($device->photos)->toHaveCount(1);
    Storage::disk('local')->assertExists($device->photos[0]);
});

test('invalid photo uploads are rejected', function (string $invalidUpload) {
    $photos = match ($invalidUpload) {
        'not an image' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')],
        'too large' => [UploadedFile::fake()->image('phone.jpg')->size(2049)],
        'too many' => array_map(fn (int $index) => UploadedFile::fake()->image('phone'.$index.'.jpg'), range(1, 6)),
    };
    $this->post(route('devices.store'), [
        'type' => 'telefoon', 'brand' => 'Samsung', 'model' => 'Galaxy S23',
        'serial_number' => 'PHOTO-123', 'condition' => 'goed', 'asking_price' => 150, 'photos' => $photos,
    ])->assertSessionHasErrors($invalidUpload === 'too many' ? 'photos' : 'photos.0');
    $this->assertDatabaseCount('devices', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with(['not an image', 'too large', 'too many']);

test('device photos are accessible to the owner and inspectors but not other customers', function () {
    Storage::disk('local')->put('devices/private.jpg', 'private photo');
    $device = Device::factory()->for($this->customer)->create(['photos' => ['devices/private.jpg']]);
    $this->get(route('devices.photo', $device))->assertOk()->assertStreamedContent('private photo')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs(User::factory()->create(['role' => 'customer']));
    $this->get(route('devices.photo', $device))->assertNotFound();
    $this->actingAs(User::factory()->create(['role' => 'inspector']));
    $this->get(route('devices.photo', $device))->assertOk()->assertStreamedContent('private photo');
});

test('shop photos serve the selected photo for approved and reserved products', function (string $status) {
    Storage::disk('local')->put('devices/front.jpg', 'front');
    Storage::disk('local')->put('devices/back.jpg', 'back');
    $device = Device::factory()->create(['status' => $status, 'photos' => ['devices/front.jpg', 'devices/back.jpg']]);
    $this->get(route('shop.photo', $device))->assertOk()->assertStreamedContent('front');
    $this->get(route('shop.photo', ['device' => $device, 'index' => 1]))->assertOk()->assertStreamedContent('back');
})->with(['goedgekeurd', 'gereserveerd']);

test('shop photos stay hidden until a device is approved', function (string $status) {
    Storage::disk('local')->put('devices/hidden.jpg', 'hidden');
    $device = Device::factory()->create(['status' => $status, 'photos' => ['devices/hidden.jpg']]);
    $this->get(route('shop.photo', $device))->assertNotFound();
})->with(['in behandeling', 'afgekeurd', 'onderhoud nodig']);

test('invalid shop photo indexes return not found', function (string $index) {
    Storage::disk('local')->put('devices/front.jpg', 'front');
    $device = Device::factory()->create(['status' => 'goedgekeurd', 'photos' => ['devices/front.jpg']]);
    $this->get(route('shop.photo', ['device' => $device, 'index' => $index]))->assertNotFound();
})->with(['-1', '99', 'abc']);

test('missing photo files return not found', function () {
    $device = Device::factory()->for($this->customer)->create([
        'status' => 'goedgekeurd', 'photos' => ['devices/missing.jpg'],
    ]);
    $this->get(route('devices.photo', $device))->assertNotFound();
    $this->get(route('shop.photo', $device))->assertNotFound();
});
