<?php

use App\Models\Device;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->inspector = User::factory()->create(['role' => 'inspector']);
    $this->device = Device::factory()->create();
    $this->checklist = [
        'status' => 'goedgekeurd', 'notes' => 'All checks passed',
        'works' => true, 'accessories_work' => true, 'presentable' => true,
        'plugs_present' => true, 'ports_work' => true, 'reset_done' => true,
        'screen_work' => true, 'battery_work' => true, 'battery_percentage' => 85,
        'video_port' => 'HDMI', 'port_types' => 'USB-C',
    ];
});

test('inspector queue prioritizes devices and excludes reservations', function () {
    $urgent = Device::factory()->create([
        'priority' => 2, 'assigned_to_user_id' => $this->inspector->id, 'photos' => ['phone.jpg'],
    ]);
    Device::factory()->create(['status' => 'gereserveerd']);
    $this->actingAs($this->inspector)->get(route('inspector.devices.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('devices/inspector')
            ->has('devices', 2)->has('inspectors', 1)->where('devices.0.id', $urgent->id)
            ->where('devices.0.assigned_inspector_name', $this->inspector->name));
});

test('inspection page includes seller and private photo links', function () {
    $this->device->update(['photos' => ['phone.jpg']]);
    $this->actingAs($this->inspector)->get(route('inspector.devices.inspect', $this->device))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('devices/inspect')
        ->where('device.customer_email', $this->device->user->email)->has('device.photo_urls', 1));
});

test('inspection saves results and excludes irrelevant fields', function (string $type) {
    $this->device->update(['type' => $type]);
    $this->freezeSecond();
    $this->actingAs($this->inspector)->patch(route('inspector.devices.update', $this->device), $this->checklist)
        ->assertRedirect(route('inspector.devices.index'))->assertSessionHasNoErrors();
    $device = $this->device->fresh();
    expect($device->status)->toBe('goedgekeurd')
        ->and($device->inspected_by_user_id)->toBe($this->inspector->id)
        ->and($device->inspected_at->equalTo(now()))->toBeTrue()
        ->and($device->inspection['notes'])->toBe('All checks passed')
        ->and($device->inspection)->not->toHaveKey('status');
    if ($type === 'console') {
        expect($device->inspection)->not->toHaveKeys(['screen_work', 'battery_work', 'battery_percentage', 'port_types']);
    } elseif ($type === 'telefoon') {
        expect($device->inspection)->not->toHaveKeys(['video_port', 'port_types']);
    } else {
        expect($device->inspection['port_types'])->toBe('USB-C');
    }
})->with(['telefoon', 'console', 'laptops']);

test('failed checks may be recorded for rejection or repair', function (string $status) {
    $this->actingAs($this->inspector)->patch(route('inspector.devices.update', $this->device), [
        ...$this->checklist, 'status' => $status, 'works' => false,
    ])->assertSessionHasNoErrors();
    expect($this->device->fresh()->status)->toBe($status)
        ->and($this->device->fresh()->inspection['works'])->toBeFalse();
})->with(['afgekeurd', 'onderhoud nodig']);

test('approval requires every relevant check', function (string $field) {
    $this->actingAs($this->inspector)->patch(route('inspector.devices.update', $this->device), [
        ...$this->checklist, $field => false,
    ])->assertSessionHasErrors($field);
    expect($this->device->fresh()->status)->toBe('in behandeling');
})->with(['works', 'accessories_work', 'presentable', 'plugs_present', 'ports_work', 'reset_done', 'screen_work', 'battery_work']);

test('invalid inspection input leaves the device unchanged', function (string $field, mixed $value) {
    $this->actingAs($this->inspector)->patch(route('inspector.devices.update', $this->device), [
        ...$this->checklist, $field => $value,
    ])->assertSessionHasErrors($field);
    expect($this->device->fresh()->inspection)->toBeNull();
})->with([
    ['status', 'gereserveerd'], ['notes', ''], ['notes', str_repeat('a', 5001)],
    ['battery_percentage', -1], ['battery_percentage', 101], ['battery_percentage', 1.5], ['works', 'invalid'],
]);

test('reserved devices cannot be opened or reinspected', function () {
    $this->device->status = 'gereserveerd';
    $this->device->save();
    $this->actingAs($this->inspector)->get(route('inspector.devices.inspect', $this->device))->assertConflict();
    $this->patch(route('inspector.devices.update', $this->device), $this->checklist)->assertSessionHasErrors('status');
    expect($this->device->fresh()->inspection)->toBeNull();
});

test('rejected devices cannot be reactivated when their serial has been reused', function () {
    $this->device->status = 'afgekeurd';
    $this->device->save();
    Device::factory()->create(['serial_number' => $this->device->serial_number]);
    $this->actingAs($this->inspector)->patch(route('inspector.devices.update', $this->device), $this->checklist)
        ->assertSessionHasErrors('status');
    expect($this->device->fresh()->status)->toBe('afgekeurd')
        ->and($this->device->fresh()->inspection)->toBeNull();
});

test('inspectors can assign and unassign devices', function () {
    $this->actingAs($this->inspector)->patch(route('inspector.devices.triage', $this->device), [
        'priority' => 2, 'assigned_to_user_id' => $this->inspector->id,
    ])->assertRedirect(route('inspector.devices.index'))->assertSessionHasNoErrors();
    expect($this->device->fresh()->assignedInspector->is($this->inspector))->toBeTrue()
        ->and($this->device->fresh()->priority)->toBe(2);
    $this->patch(route('inspector.devices.triage', $this->device), [
        'priority' => 0, 'assigned_to_user_id' => null,
    ])->assertSessionHasNoErrors();
    expect($this->device->fresh()->assigned_to_user_id)->toBeNull();
});

test('triage rejects invalid priorities and customer assignments', function () {
    $this->actingAs($this->inspector)->patch(route('inspector.devices.triage', $this->device), [
        'priority' => 3, 'assigned_to_user_id' => $this->device->user_id,
    ])->assertSessionHasErrors(['priority', 'assigned_to_user_id']);
    expect($this->device->fresh()->priority)->toBe(1)
        ->and($this->device->fresh()->assigned_to_user_id)->toBeNull();
});

test('reserved devices cannot be reassigned', function () {
    $this->device->status = 'gereserveerd';
    $this->device->save();
    $this->actingAs($this->inspector)->patch(route('inspector.devices.triage', $this->device), [
        'priority' => 2, 'assigned_to_user_id' => $this->inspector->id,
    ])->assertSessionHasErrors('priority');
    expect($this->device->fresh()->priority)->toBe(1);
});

test('customers cannot access inspection routes', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('inspector.devices.index'))->assertForbidden();
    $this->get(route('inspector.devices.inspect', $this->device))->assertForbidden();
    $this->patch(route('inspector.devices.update', $this->device), $this->checklist)->assertForbidden();
    $this->patch(route('inspector.devices.triage', $this->device), ['priority' => 2])->assertForbidden();
    expect($this->device->fresh()->status)->toBe('in behandeling');
});
