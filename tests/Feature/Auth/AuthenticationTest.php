<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

/** Een verkeerd wachtwoord houdt de gebruiker uitgelogd en toont de Nederlandse foutmelding. */
test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors([
        'password' => 'E-mailadres of wachtwoord is onjuist.',
    ]);

    $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page
        ->component('auth/login')
        ->where('errors.password', 'E-mailadres of wachtwoord is onjuist.')
    );

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

/** Controleert doorverwijzingen van login en home via het dashboard naar de winkel. */
test('signed in inspectors reach the shop through the dashboard from login and home', function () {
    $user = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($user)->get('/login')->assertRedirect(route('dashboard'));
    $this->get('/')->assertRedirect(route('dashboard'));
    $this->get('/dashboard')->assertRedirect(route('shop.index'));
    $this->get(route('shop.index'))->assertOk();
});
