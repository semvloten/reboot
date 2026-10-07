<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('auth/register')
        ->where('canCreateInspector', false)
    );
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'role' => 'customer',
    ]);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('inspectors can open registration with the role option', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($inspector)->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/register')
            ->where('canCreateInspector', true)
        );
});

/** Test beide accountrollen en controleert dat de keurmeester zelf ingelogd blijft. */
test('inspectors can create accounts and remain signed in', function (bool $isInspector, string $expectedRole) {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $response = $this->actingAs($inspector)->post(route('register'), [
        'name' => 'New Account',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_inspector' => $isInspector,
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('register'))
        ->assertSessionHas('status', 'Het account is aangemaakt.');
    $this->assertAuthenticatedAs($inspector);

    $createdUser = User::where('email', 'new@example.com')->firstOrFail();
    expect($createdUser->role)->toBe($expectedRole);
    expect(Hash::check('password', $createdUser->password))->toBeTrue();

    $this->get(route('register'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('canCreateInspector', true)
        ->where('status', 'Het account is aangemaakt.')
    );
})->with([
    'checked creates inspector' => [true, 'inspector'],
    'unchecked creates customer' => [false, 'customer'],
]);

test('customers are redirected away from registration', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->get(route('register'))
        ->assertRedirect(route('dashboard'));
});

test('customers cannot create accounts through registration', function (bool $isInspector) {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->post(route('register'), [
        'name' => 'New Account',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_inspector' => $isInspector,
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    $this->assertAuthenticatedAs($customer);
})->with([true, false]);

/** Een bezoeker mag zichzelf via registratie geen keurmeesterrechten geven. */
test('guests cannot request an inspector account', function () {
    $this->post(route('register'), [
        'name' => 'New Account',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_inspector' => true,
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    $this->assertGuest();
});

/** Een handmatig meegestuurde rol mag de veilige standaardrol customer niet overschrijven. */
test('a supplied role does not grant inspector access during public registration', function () {
    $this->post(route('register'), [
        'name' => 'New Account',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_inspector' => false,
        'role' => 'inspector',
    ])->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'email' => 'new@example.com',
        'role' => 'customer',
    ]);
});

test('invalid role options do not create accounts', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($inspector)->from(route('register'))->post(route('register'), [
        'name' => 'New Account',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_inspector' => 'invalid',
    ])->assertSessionHasErrors('is_inspector');

    $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    $this->assertAuthenticatedAs($inspector);
});
