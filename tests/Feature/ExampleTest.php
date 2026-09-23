<?php

use App\Models\User;

it('redirects guests from application pages and actions to login', function (string $method, string $uri) {
    $this->call($method, $uri)->assertRedirect(route('login'));
    $this->assertGuest();
})->with([
    ['GET', '/'],
    ['GET', '/dashboard'],
    ['GET', '/db-test'],
    ['GET', '/inspector'],
    ['GET', '/settings'],
    ['GET', '/settings/profile'],
    ['GET', '/settings/password'],
    ['GET', '/settings/appearance'],
    ['PATCH', '/settings/profile'],
    ['DELETE', '/settings/profile'],
    ['PUT', '/settings/password'],
]);

it('allows authenticated users to visit home', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
});

it('keeps account access pages available to guests', function (string $uri) {
    $this->get($uri)->assertOk();
})->with(['/login', '/register', '/forgot-password']);

it('still requires the inspector role after login', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get('/inspector')
        ->assertForbidden();
});
