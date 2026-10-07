<?php

use App\Models\Device;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/** Logt voor elke winkeltest een nieuw klantaccount in. */
beforeEach(function () {
    $this->customer = User::factory()->create(['role' => 'customer']);
    $this->actingAs($this->customer);
});

/** Controleert welke statussen zichtbaar zijn en of de goedkoopste producten eerst komen. */
test('the shop lists approved and reserved products ordered by price', function () {
    $approved = Device::factory()->create(['status' => 'goedgekeurd', 'asking_price' => 100]);
    $reserved = Device::factory()->create(['status' => 'gereserveerd', 'asking_price' => 200]);
    foreach (['in behandeling', 'afgekeurd', 'onderhoud nodig'] as $status) {
        Device::factory()->create(['status' => $status]);
    }
    $this->get(route('shop.index'))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('dashboard')
            ->has('devices', 2)->where('devices.0.id', $approved->id)->where('devices.1.id', $reserved->id));
});

test('the shop supports an empty catalog', function () {
    $this->get(route('shop.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component('dashboard')->has('devices', 0));
});

test('product details include the inspection report and reservation availability', function (string $status, bool $canReserve) {
    $device = Device::factory()->create(['status' => $status, 'inspection' => ['notes' => 'Alles werkt.']]);
    $this->get(route('shop.show', $device))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('devices/product')
            ->where('device.id', $device->id)->where('device.inspection.notes', 'Alles werkt.')
            ->where('canReserve', $canReserve));
})->with([['goedgekeurd', true], ['gereserveerd', false]]);

/** Ook directe productlinks en reserveringsaanvragen mogen de keuring niet omzeilen. */
test('unapproved products cannot be viewed checked out or reserved', function (string $status) {
    $device = Device::factory()->create(['status' => $status]);
    $this->get(route('shop.show', $device))->assertNotFound();
    $this->get(route('shop.checkout', $device))->assertNotFound();
    $this->post(route('shop.reserve', $device))->assertNotFound();
    expect($device->fresh()->status)->toBe($status)->and($device->fresh()->reserved_by_user_id)->toBeNull();
})->with(['in behandeling', 'afgekeurd', 'onderhoud nodig']);

test('customers can open checkout for an approved product', function () {
    $device = Device::factory()->create(['status' => 'goedgekeurd']);
    $this->get(route('shop.checkout', $device))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('devices/checkout')
            ->where('device.id', $device->id)->where('device.asking_price', '150.00'));
});

test('customers can reserve an approved product', function () {
    $device = Device::factory()->create(['status' => 'goedgekeurd']);
    $this->post(route('shop.reserve', $device))->assertRedirect(route('shop.show', $device))
        ->assertSessionHas('status', 'Je reservering is bevestigd. Dit product is voor jou gereserveerd.');
    $this->assertDatabaseHas('devices', [
        'id' => $device->id, 'status' => 'gereserveerd', 'reserved_by_user_id' => $this->customer->id,
    ]);
});

/** Een tweede klant mag een bestaande reservering niet overnemen. */
test('a second customer cannot take an existing reservation', function () {
    $device = Device::factory()->create(['status' => 'goedgekeurd']);
    $this->post(route('shop.reserve', $device));
    $this->actingAs(User::factory()->create(['role' => 'customer']));
    $this->post(route('shop.reserve', $device))->assertRedirect(route('shop.show', $device))
        ->assertSessionHas('status', 'Dit product is niet meer beschikbaar. Er is geen reservering gemaakt.');
    $this->get(route('shop.checkout', $device))->assertRedirect(route('shop.show', $device));
    expect($device->fresh()->reserved_by_user_id)->toBe($this->customer->id);
});

test('inspectors can view products but cannot use checkout or reserve them', function () {
    $device = Device::factory()->create(['status' => 'goedgekeurd']);
    $this->actingAs(User::factory()->create(['role' => 'inspector']));
    $this->get(route('shop.show', $device))->assertInertia(fn (AssertableInertia $page) => $page
        ->component('devices/product')->where('canReserve', false));
    $this->get(route('shop.checkout', $device))->assertForbidden();
    $this->post(route('shop.reserve', $device))->assertForbidden();
    expect($device->fresh()->status)->toBe('goedgekeurd')->and($device->fresh()->reserved_by_user_id)->toBeNull();
});
