<?php

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

test('login page is available', function () {
    $this->get(route('login'))->assertOk();
});

test('users can log in and log out', function () {
    $user = User::factory()->create();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false))->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('invalid credentials do not authenticate', function () {
    $user = User::factory()->create();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('login validates required credentials', function () {
    $this->post(route('login'), [])->assertSessionHasErrors(['email', 'password']);
    $this->assertGuest();
});

test('successful login clears earlier failures and honors the intended destination', function () {
    $user = User::factory()->create();
    $key = strtolower($user->email).'|127.0.0.1';
    RateLimiter::hit($key);

    $this->withSession(['url.intended' => route('devices.index')])
        ->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('devices.index'));

    expect(RateLimiter::attempts($key))->toBe(0);
    $this->assertAuthenticatedAs($user);
});

test('repeated failed logins trigger a lockout', function () {
    Event::fake([Lockout::class]);
    $user = User::factory()->create();
    $key = strtolower($user->email).'|127.0.0.1';
    RateLimiter::clear($key);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('password');
    }
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    Event::assertDispatched(Lockout::class);
    $this->assertGuest();
    RateLimiter::clear($key);
});
