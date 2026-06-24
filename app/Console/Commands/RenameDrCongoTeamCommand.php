<?php

namespace App\Console\Commands;

use App\Cache\TournamentCache;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RenameDrCongoTeamCommand extends Command
{
    private const string NEW_NAME = 'DR Congo';

    /** @var list<string> */
    private const array OLD_NAMES = [
        'Democratic Republic of the Congo',
        'Democratic Republic of Congo',
    ];

    protected $signature = 'worldcup:rename-dr-congo';

    protected $description = 'Rename Democratic Republic of the Congo to DR Congo in the database and source data';

    public function handle(TournamentCache $cache): int
    {
        $team = Team::query()
            ->where('code', 'COD')
            ->orWhereIn('name', self::OLD_NAMES)
            ->first();

        if ($team === null) {
            $this->error('DR Congo team (COD) was not found.');

            return self::FAILURE;
        }

        if ($team->name === self::NEW_NAME) {
            $this->info('DR Congo is already named correctly.');

            return self::SUCCESS;
        }

        $previousName = $team->name;
        $team->update(['name' => self::NEW_NAME]);

        $this->updateSourceData();

        foreach (['predict', 'bracket'] as $domain) {
            $cache->bump($domain);
        }

        $this->info("Renamed [{$previousName}] to [".self::NEW_NAME.'].');

        return self::SUCCESS;
    }

    private function updateSourceData(): void
    {
        $path = database_path('data/football.teams.json');
        $contents = File::get($path);
        $teams = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        $updated = false;

        foreach ($teams as &$team) {
            if (($team['fifa_code'] ?? null) !== 'COD') {
                continue;
            }

            $team['name_en'] = self::NEW_NAME;
            $updated = true;
        }
        unset($team);

        if (! $updated) {
            $this->warn('COD team was not found in football.teams.json.');

            return;
        }

        File::put(
            $path,
            json_encode($teams, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );
    }
}
