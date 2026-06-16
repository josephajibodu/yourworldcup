<?php

use App\Models\User;

it('returns the registered player count as json', function () {
    User::factory()->count(3)->create();

    $this->getJson('/players/count')
        ->assertOk()
        ->assertExactJson(['count' => 3]);
});

it('returns zero when there are no players', function () {
    $this->getJson('/players/count')
        ->assertOk()
        ->assertExactJson(['count' => 0]);
});
