<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

/** Het dashboard verwijst door naar de winkel voor ingelogde gebruikers. */
test('authenticated users are redirected from the dashboard to the shop', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertRedirect(route('shop.index'));
    $this->get(route('shop.index'))->assertOk();
});
