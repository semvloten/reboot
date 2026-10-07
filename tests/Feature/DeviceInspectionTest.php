<?php

use App\Models\Device;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/** Begint elke test als keurmeester met een geldige, volledig ingevulde telefoonkeuring. */
beforeEach(function () {
    $this->inspector = User::factory()->create(['role' => 'inspector']);
    $this->inspection = [
        'status' => 'goedgekeurd', 'notes' => 'Alle controles zijn geslaagd.',
        'works' => true, 'accessories_work' => true, 'presentable' => true,
        'plugs_present' => true, 'ports_work' => true, 'reset_done' => true,
        'screen_work' => true, 'battery_work' => true, 'battery_percentage' => 90,
    ];
    $this->actingAs($this->inspector);
});

test('the inspector overview shows submissions and customer names but excludes reservations', function () {
    $device = Device::factory()->create();
    Device::factory()->create(['status' => 'gereserveerd']);
    $this->get(route('inspector'))->assertRedirect(route('inspector.devices.index'));
    $this->get(route('inspector.devices.index'))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('devices/inspector')
            ->has('devices', 1)->where('devices.0.id', $device->id)
            ->where('devices.0.customer_name', $device->user->name));
});

test('inspectors can open an inspection with its saved notes', function () {
    $device = Device::factory()->create(['inspection' => ['notes' => 'Scherm controleren.']]);
    $this->get(route('inspector.devices.inspect', $device))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('devices/inspect')
            ->where('device.id', $device->id)->where('device.inspection.notes', 'Scherm controleren.'));
});

test('inspectors can save approval rejection and repair decisions', function (string $status) {
    $device = Device::factory()->create();
    $this->inspection['status'] = $status;
    $this->inspection['works'] = $status === 'goedgekeurd';
    $this->patch(route('inspector.devices.update', $device), $this->inspection)
        ->assertRedirect(route('inspector.devices.inspect', $device))->assertSessionHasNoErrors()->assertSessionHas('status');
    $device->refresh();
    expect($device->status)->toBe($status)
        ->and($device->inspection)->toBe(collect($this->inspection)->except('status')->all())
        ->and($device->inspected_by_user_id)->toBe($this->inspector->id)
        ->and($device->inspected_at)->not->toBeNull();
})->with(['goedgekeurd', 'afgekeurd', 'onderhoud nodig']);

/** Elke verplichte controle wordt apart uitgezet om onterechte goedkeuring te voorkomen. */
test('approval requires every necessary check to pass', function (string $check) {
    $device = Device::factory()->create();
    $this->inspection[$check] = false;
    $this->patch(route('inspector.devices.update', $device), $this->inspection)->assertSessionHasErrors($check);
    expect($device->fresh()->status)->toBe('in behandeling')->and($device->fresh()->inspection)->toBeNull();
})->with(['works', 'accessories_work', 'presentable', 'plugs_present', 'ports_work', 'reset_done', 'screen_work', 'battery_work']);

test('invalid inspection details do not change the device', function (string $field, mixed $value) {
    $device = Device::factory()->create();
    $this->inspection[$field] = $value;
    $this->patch(route('inspector.devices.update', $device), $this->inspection)->assertSessionHasErrors($field);
    expect($device->fresh()->status)->toBe('in behandeling');
})->with([
    'notes required' => ['notes', ''], 'invalid status' => ['status', 'gereserveerd'],
    'battery too high' => ['battery_percentage', 101],
]);

test('consoles require a video port instead of battery and screen checks', function () {
    $device = Device::factory()->create(['type' => 'console']);
    $inspection = collect($this->inspection)->except(['screen_work', 'battery_work', 'battery_percentage'])->all();
    $this->patch(route('inspector.devices.update', $device), $inspection)->assertSessionHasErrors('video_port');
    $this->patch(route('inspector.devices.update', $device), $inspection + ['video_port' => 'HDMI'])->assertSessionHasNoErrors();
    expect($device->fresh()->inspection)->toHaveKey('video_port', 'HDMI')->not->toHaveKey('battery_percentage');
});

test('laptop inspections require the connection types', function () {
    $device = Device::factory()->create(['type' => 'laptops']);
    $this->patch(route('inspector.devices.update', $device), $this->inspection)->assertSessionHasErrors('port_types');
    $this->patch(route('inspector.devices.update', $device), $this->inspection + ['port_types' => 'USB-C, HDMI'])->assertSessionHasNoErrors();
    expect($device->fresh()->inspection)->toHaveKey('port_types', 'USB-C, HDMI');
});

/** Een reservering beschermt het apparaat tegen verdere wijzigingen via de keuring. */
test('reserved devices cannot be opened or changed for inspection', function () {
    $device = Device::factory()->create(['status' => 'gereserveerd', 'reserved_by_user_id' => User::factory()->create()->id]);
    $this->get(route('inspector.devices.inspect', $device))->assertStatus(409);
    $this->patch(route('inspector.devices.update', $device), $this->inspection)->assertSessionHasErrors('status');
    expect($device->fresh()->status)->toBe('gereserveerd')->and($device->fresh()->inspection)->toBeNull();
});

test('customers cannot access or submit inspections', function () {
    $device = Device::factory()->create();
    $this->actingAs(User::factory()->create(['role' => 'customer']));
    $this->get(route('inspector'))->assertForbidden();
    $this->get(route('inspector.devices.index'))->assertForbidden();
    $this->get(route('inspector.devices.inspect', $device))->assertForbidden();
    $this->patch(route('inspector.devices.update', $device), $this->inspection)->assertForbidden();
    expect($device->fresh()->inspection)->toBeNull();
});
