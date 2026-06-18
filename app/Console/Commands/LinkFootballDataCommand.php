<?php

namespace App\Console\Commands;

use App\FootballData\FootballDataLinker;
use Illuminate\Console\Command;

class LinkFootballDataCommand extends Command
{
    protected $signature = 'football-data:link
                            {--teams : Link teams only}
                            {--fixtures : Link fixtures only}';

    protected $description = 'Link local teams and fixtures to football-data.org ids from database/data/football_data_org';

    public function handle(FootballDataLinker $linker): int
    {
        $teamsOnly = (bool) $this->option('teams');
        $fixturesOnly = (bool) $this->option('fixtures');
        $linkTeams = ! $fixturesOnly;
        $linkFixtures = ! $teamsOnly;

        if ($linkTeams) {
            $this->report('Teams', $linker->linkTeams());
        }

        if ($linkFixtures) {
            $this->report('Fixtures', $linker->linkFixtures());
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{linked: int, skipped: int, unmatched: list<string>}  $result
     */
    private function report(string $label, array $result): void
    {
        $this->info(sprintf(
            '%s: linked %d, already linked %d, unmatched %d.',
            $label,
            $result['linked'],
            $result['skipped'],
            count($result['unmatched']),
        ));

        foreach ($result['unmatched'] as $message) {
            $this->warn($message);
        }
    }
}
