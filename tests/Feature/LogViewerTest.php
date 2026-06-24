<?php

use App\Models\User;

test('guests cannot access log viewer', function () {
    $this->get('/'.config('log-viewer.route_path'))
        ->assertForbidden();
});

test('non-admin users cannot access log viewer', function () {
    $this->actingAs(User::factory()->create())
        ->get('/'.config('log-viewer.route_path'))
        ->assertForbidden();
});

test('site admins can access log viewer', function () {
    $this->actingAs(siteAdmin())
        ->get('/'.config('log-viewer.route_path'))
        ->assertOk();
});
