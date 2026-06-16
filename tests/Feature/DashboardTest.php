<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('non-admin users cannot visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('site admins can visit the dashboard', function () {
    $this->actingAs(siteAdmin())
        ->get(route('dashboard'))
        ->assertOk();
});
