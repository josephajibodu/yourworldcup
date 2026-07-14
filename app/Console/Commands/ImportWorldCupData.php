<?php

namespace App\Console\Commands;

use App\FootballData\FootballDataLinker;
use App\Models\Fixture;
use App\Models\Stadium;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Console\Command;

class ImportWorldCupData extends Command
{
    protected $signature = 'worldcup:import';

    protected $description = 'Import World Cup teams, stadiums, and fixtures from database/data and attach enabled markets (overwrites fixture data from JSON — use markets:attach-enabled to add markets only)';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => PredictionMarketSeeder::class,
            '--force' => true,
        ]);

        WorldCupImporter::fromDefaultPath()->import();

        $teamLinks = FootballDataLinker::fromConfig()->linkTeams();
        $fixtureLinks = FootballDataLinker::fromConfig()->linkFixtures();

        $this->info(sprintf(
            'Imported %d teams, %d stadiums, %d fixtures.',
            Team::count(),
            Stadium::count(),
            Fixture::count(),
        ));

        $this->info(sprintf(
            'Linked football-data ids for %d teams and %d fixtures.',
            $teamLinks['linked'] + $teamLinks['skipped'],
            $fixtureLinks['linked'] + $fixtureLinks['skipped'],
        ));

        return self::SUCCESS;
    }
}
