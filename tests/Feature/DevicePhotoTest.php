<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('devices/phone.jpg', 'photo bytes');
    $this->device = Device::factory()->create(['photos' => ['devices/phone.jpg']]);
});

test('owner and inspector can read private photos', function (string $role) {
    $user = $role === 'owner' ? $this->device->user : User::factory()->create(['role' => 'inspector']);
    $response = $this->actingAs($user)->get(route('devices.photo', $this->device))->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->streamedContent())->toBe('photo bytes')
        ->and($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
})->with(['owner', 'inspector']);

test('other customers cannot read private photos', function () {
    $this->actingAs(User::factory()->create())->get(route('devices.photo', $this->device))->assertNotFound();
});

test('shop photos are visible only for approved or reserved devices', function (string $status, int $expected) {
    $this->device->status = $status;
    $this->device->save();
    $response = $this->actingAs(User::factory()->create())->get(route('shop.photo', $this->device))->assertStatus($expected);
    if ($expected === 200) {
        expect($response->streamedContent())->toBe('photo bytes');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
})->with([
    ['goedgekeurd', 200], ['gereserveerd', 200], ['afgekeurd', 404],
    ['in behandeling', 404], ['onderhoud nodig', 404],
]);

test('invalid photo indexes cannot retrieve files', function (string $route, mixed $index) {
    $this->device->status = 'goedgekeurd';
    $this->device->save();
    $this->actingAs($this->device->user)->get(route($route, [
        'device' => $this->device, 'index' => $index,
    ]))->assertNotFound();
})->with(['devices.photo', 'shop.photo'])->with([-1, 'invalid', 99, '0.5', [['0']]]);

test('missing photo files and empty photo lists return not found', function (string $route, bool $empty) {
    $this->device->status = 'goedgekeurd';
    $this->device->photos = $empty ? [] : ['missing.jpg'];
    $this->device->save();
    $this->actingAs($this->device->user)->get(route($route, $this->device))->assertNotFound();
})->with(['devices.photo', 'shop.photo'])->with([true, false]);
