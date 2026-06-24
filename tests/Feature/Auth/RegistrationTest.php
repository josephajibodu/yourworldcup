<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'joseph_ajibodu',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('predict', absolute: false));

    expect(auth()->user()?->name)->toBe('joseph_ajibodu');
});

test('registration normalizes leading at signs on x handles', function () {
    $this->post(route('register.store'), [
        'name' => '@new_player',
        'email' => 'player@example.com',
        'password' => 'password',
    ])->assertRedirect(route('predict', absolute: false));

    expect(auth()->user()?->name)->toBe('new_player');
});

test('registration rejects invalid x handles', function () {
    $this->post(route('register.store'), [
        'name' => 'not a handle',
        'email' => 'invalid@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('name');

    $this->assertGuest();
});
