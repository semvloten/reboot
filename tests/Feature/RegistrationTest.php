<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->registration = [
        'name' => 'New Customer', 'email' => 'new@example.com',
        'password' => 'secure-password', 'password_confirmation' => 'secure-password',
    ];
});

test('guests can register as customers and passwords are hashed', function () {
    $this->get(route('register'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canCreateInspector', false));
    $this->post(route('register'), [...$this->registration, 'role' => 'inspector'])
        ->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
    $user = User::sole();
    expect($user->role)->toBe('customer')
        ->and(Hash::check('secure-password', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('guests cannot create inspector accounts', function () {
    $this->post(route('register'), [...$this->registration, 'is_inspector' => true])->assertForbidden();
    $this->assertDatabaseCount('users', 0);
});

test('inspectors can create accounts without changing their session', function (bool $isInspector) {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $this->actingAs($inspector)->get(route('register'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canCreateInspector', true));
    $this->post(route('register'), [...$this->registration, 'is_inspector' => $isInspector])
        ->assertRedirect(route('register'))->assertSessionHasNoErrors();
    expect(User::where('email', 'new@example.com')->sole()->role)->toBe($isInspector ? 'inspector' : 'customer');
    $this->assertAuthenticatedAs($inspector);
})->with([true, false]);

test('signed in customers cannot create more accounts', function () {
    $this->actingAs(User::factory()->create())->get(route('register'))->assertRedirect(route('dashboard'));
    $this->post(route('register'), $this->registration)->assertForbidden();
    $this->assertDatabaseCount('users', 1);
});

test('registration rejects invalid fields', function (string $field, string $value) {
    $this->post(route('register'), [...$this->registration, $field => $value])->assertSessionHasErrors($field);
    $this->assertDatabaseCount('users', 0);
})->with([['name', ''], ['email', 'invalid'], ['password', 'short']]);

test('registration rejects duplicate email addresses', function () {
    User::factory()->create(['email' => 'new@example.com']);
    $this->post(route('register'), $this->registration)->assertSessionHasErrors('email');
    $this->assertDatabaseCount('users', 1);
});
