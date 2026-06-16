<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\Stadium;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Console\Command;

class ImportWorldCupData extends Command
{
    protected $signature = 'worldcup:import';

    protected $description = 'Import World Cup teams, stadiums, and fixtures from database/data and attach enabled markets';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => PredictionMarketSeeder::class,
            '--force' => true,
        ]);

        WorldCupImporter::fromDefaultPath()->import();

        $this->info(sprintf(
            'Imported %d teams, %d stadiums, %d fixtures.',
            Team::count(),
            Stadium::count(),
            Fixture::count(),
        ));

        return self::SUCCESS;
    }
}
