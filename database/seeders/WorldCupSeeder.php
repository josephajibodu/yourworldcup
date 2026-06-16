<?php

namespace Database\Seeders;

use App\Predictions\Importing\WorldCupImporter;
use Illuminate\Database\Seeder;

class WorldCupSeeder extends Seeder
{
    public function run(): void
    {
        WorldCupImporter::fromDefaultPath()->import();
    }
}
