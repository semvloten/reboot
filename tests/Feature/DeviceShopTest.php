<?php

use App\Models\Device;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->device = Device::factory()->create(['status' => 'goedgekeurd']);
});

test('shop lists only approved and reserved devices in price order', function () {
    $cheap = Device::factory()->create(['status' => 'gereserveerd', 'asking_price' => 20, 'photos' => ['photo.jpg']]);
    foreach (['afgekeurd', 'in behandeling', 'onderhoud nodig'] as $status) {
        Device::factory()->create(['status' => $status]);
    }
    $this->actingAs($this->customer)->get(route('shop.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard')->has('devices', 2)
            ->where('devices.0.id', $cheap->id)
            ->where('devices.0.photo_url', route('shop.photo', $cheap))
            ->where('devices.1.photo_url', null));
});

test('unapproved devices are hidden from product checkout and reservation endpoints', function (string $status) {
    $this->device->status = $status;
    $this->device->save();
    $this->actingAs($this->customer);
    $this->get(route('shop.show', $this->device))->assertNotFound();
    $this->get(route('shop.checkout', $this->device))->assertNotFound();
    $this->post(route('shop.reserve', $this->device))->assertNotFound();
    expect($this->device->fresh()->reserved_by_user_id)->toBeNull();
})->with(['afgekeurd', 'in behandeling', 'onderhoud nodig']);

test('product page exposes reservation eligibility and photo links', function () {
    $this->device->update(['photos' => ['photo.jpg']]);
    $this->actingAs($this->customer)->get(route('shop.show', $this->device))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('devices/product')
            ->where('canReserve', true)->where('isOwnDevice', false)->has('device.photo_urls', 1));
    $this->actingAs($this->device->user)->get(route('shop.show', $this->device))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canReserve', false)->where('isOwnDevice', true));
});

test('customer can open checkout and reserve another sellers device once', function () {
    $this->actingAs($this->customer)->get(route('shop.checkout', $this->device))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('devices/checkout')->where('device.id', $this->device->id));
    $this->post(route('shop.reserve', $this->device))->assertRedirect(route('shop.show', $this->device))->assertSessionHas('status');
    expect($this->device->fresh()->status)->toBe('gereserveerd')
        ->and($this->device->fresh()->reservedBy->is($this->customer))->toBeTrue();
    $this->actingAs(User::factory()->create())->post(route('shop.reserve', $this->device))
        ->assertRedirect(route('shop.show', $this->device))
        ->assertSessionHas('status', 'Dit product is niet meer beschikbaar. Er is geen reservering gemaakt.');
    expect($this->device->fresh()->reserved_by_user_id)->toBe($this->customer->id);
    $this->get(route('shop.checkout', $this->device))->assertRedirect(route('shop.show', $this->device));
});

test('seller cannot check out or reserve their own device', function () {
    $this->actingAs($this->device->user);
    $this->get(route('shop.checkout', $this->device))->assertRedirect(route('shop.show', $this->device));
    $this->post(route('shop.reserve', $this->device))->assertRedirect(route('shop.show', $this->device));
    expect($this->device->fresh()->status)->toBe('goedgekeurd')
        ->and($this->device->fresh()->reserved_by_user_id)->toBeNull();
});

test('inspectors can view products but cannot reserve them', function () {
    $this->actingAs(User::factory()->create(['role' => 'inspector']));
    $this->get(route('shop.show', $this->device))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canReserve', false));
    $this->get(route('shop.checkout', $this->device))->assertForbidden();
    $this->post(route('shop.reserve', $this->device))->assertForbidden();
});

test('reservation list contains only current customers reservations', function () {
    $reserved = Device::factory()->create([
        'status' => 'gereserveerd', 'reserved_by_user_id' => $this->customer->id, 'photos' => ['photo.jpg'],
    ]);
    Device::factory()->create(['status' => 'gereserveerd', 'reserved_by_user_id' => User::factory()]);
    $this->actingAs($this->customer)->get(route('devices.reservations'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('devices/reservations')
            ->has('reservedDevices', 1)->where('reservedDevices.0.id', $reserved->id)
            ->where('reservedDevices.0.photo_url', route('shop.photo', $reserved)));
});

test('guests are redirected to login from protected pages and actions', function () {
    foreach (['shop.index', 'devices.index', 'devices.create', 'devices.reservations', 'inspector.devices.index'] as $name) {
        $this->get(route($name))->assertRedirect(route('login'));
    }
    $this->post(route('shop.reserve', $this->device))->assertRedirect(route('login'));
    $this->post(route('devices.store'), [])->assertRedirect(route('login'));
});

test('empty customer lists render successfully', function () {
    $this->actingAs($this->customer)->get(route('devices.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('devices', 0));
    $this->get(route('devices.reservations'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('reservedDevices', 0));
});
