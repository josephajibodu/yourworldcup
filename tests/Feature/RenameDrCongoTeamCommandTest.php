<?php

use App\Models\Team;
use Illuminate\Support\Facades\File;

it('renames democratic republic of the congo to dr congo', function () {
    $team = Team::factory()->create([
        'external_id' => '42',
        'name' => 'Democratic Republic of the Congo',
        'code' => 'COD',
        'iso2' => 'CD',
    ]);

    $this->artisan('worldcup:rename-dr-congo')->assertSuccessful();

    expect($team->fresh()->name)->toBe('DR Congo');
});

it('is idempotent when dr congo is already named correctly', function () {
    Team::factory()->create([
        'name' => 'DR Congo',
        'code' => 'COD',
    ]);

    $this->artisan('worldcup:rename-dr-congo')->assertSuccessful();

    expect(Team::query()->where('code', 'COD')->value('name'))->toBe('DR Congo');
});

it('updates the source team json file', function () {
    $path = database_path('data/football.teams.json');
    $teams = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

    foreach ($teams as &$team) {
        if (($team['fifa_code'] ?? null) === 'COD') {
            $team['name_en'] = 'Democratic Republic of the Congo';
        }
    }
    unset($team);

    File::put($path, json_encode($teams, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    Team::factory()->create([
        'name' => 'Democratic Republic of the Congo',
        'code' => 'COD',
    ]);

    $this->artisan('worldcup:rename-dr-congo')->assertSuccessful();

    $updated = collect(json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR))
        ->firstWhere('fifa_code', 'COD');

    expect($updated['name_en'])->toBe('DR Congo');
});
