<?php

use App\Models\Device;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create(['role' => 'customer']);
    $this->submission = [
        'type' => 'telefoon', 'brand' => 'Samsung', 'model' => 'Galaxy S23',
        'serial_number' => ' reboot-123 ', 'condition' => 'goed',
        'accessories' => 'Oplader', 'asking_price' => '150,50',
    ];
});

test('customers can open the device submission page', function () {
    $this->actingAs($this->customer)->get(route('devices.create'))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('devices/create'));
});

test('submissions save the owner and normalize the serial number and price', function () {
    $this->actingAs($this->customer)->post(route('devices.store'), $this->submission + [
        'user_id' => User::factory()->create()->id, 'status' => 'goedgekeurd',
    ])->assertRedirect(route('devices.create'))->assertSessionHasNoErrors()->assertSessionHas('status');

    $this->assertDatabaseHas('devices', [
        'user_id' => $this->customer->id, 'serial_number' => 'REBOOT-123',
        'asking_price' => '150.50', 'status' => 'in behandeling',
        'brand' => 'Samsung', 'model' => 'Galaxy S23', 'accessories' => 'Oplader',
    ]);
});

test('duplicate serial numbers are rejected regardless of casing and whitespace', function () {
    Device::factory()->create(['serial_number' => 'REBOOT-123']);
    $this->actingAs($this->customer)->post(route('devices.store'), $this->submission)
        ->assertSessionHasErrors('serial_number');
    $this->assertDatabaseCount('devices', 1);
});

test('invalid device details are rejected', function (string $field, mixed $value) {
    $this->submission[$field] = $value;
    $this->actingAs($this->customer)->post(route('devices.store'), $this->submission)->assertSessionHasErrors($field);
    $this->assertDatabaseCount('devices', 0);
})->with([
    'missing brand' => ['brand', ''], 'missing model' => ['model', ''],
    'missing serial' => ['serial_number', ''], 'unsupported type' => ['type', 'printer'],
    'invalid condition' => ['condition', 'kapot'], 'negative price' => ['asking_price', '-1'],
    'too many decimals' => ['asking_price', '12.345'],
]);

test('my devices shows only owned devices with their inspection notes', function () {
    $device = Device::factory()->for($this->customer)->create([
        'status' => 'onderhoud nodig', 'inspection' => ['notes' => 'Batterij vervangen.'],
    ]);
    Device::factory()->create();
    $this->actingAs($this->customer)->get(route('devices.index'))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('devices/index')
            ->has('devices', 1)->where('devices.0.id', $device->id)
            ->where('devices.0.status', 'onderhoud nodig')
            ->where('devices.0.inspection.notes', 'Batterij vervangen.'));
});

test('my devices supports customers without submissions', function () {
    $this->actingAs($this->customer)->get(route('devices.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('devices/index')->has('devices', 0));
});

test('inspectors cannot submit devices or open customer device pages', function () {
    $this->actingAs(User::factory()->create(['role' => 'inspector']));
    $this->get(route('devices.create'))->assertForbidden();
    $this->get(route('devices.index'))->assertForbidden();
    $this->post(route('devices.store'), $this->submission)->assertForbidden();
    $this->assertDatabaseCount('devices', 0);
});

test('guests must sign in to use custom pages and actions', function () {
    $device = Device::factory()->create();
    foreach (['shop.index', 'devices.index', 'devices.create', 'inspector.devices.index', 'inspector'] as $routeName) {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }
    foreach (['shop.show', 'shop.checkout', 'shop.photo', 'devices.photo', 'inspector.devices.inspect'] as $routeName) {
        $this->get(route($routeName, $device))->assertRedirect(route('login'));
    }
    $this->post(route('devices.store'), $this->submission)->assertRedirect(route('login'));
    $this->post(route('shop.reserve', $device))->assertRedirect(route('login'));
    $this->patch(route('inspector.devices.update', $device), [])->assertRedirect(route('login'));
});
