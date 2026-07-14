<?php

namespace App\Console\Commands;

use App\Fixtures\FixtureInspector;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ShowFixtureCommand extends Command
{
    protected $signature = 'fixture:show {match : Tournament match number (e.g. 101 or M101)}';

    protected $description = 'Show a fixture by tournament match number';

    public function handle(FixtureInspector $inspector): int
    {
        try {
            $fixture = $inspector->findByMatchNumber((string) $this->argument('match'));
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $summary = $inspector->summarize($fixture);

        $this->components->twoColumnDetail('Match', 'M'.$summary['externalId']);
        $this->components->twoColumnDetail('Fixture id', (string) $summary['id']);
        $this->components->twoColumnDetail('Stage', $summary['stage']);
        $this->components->twoColumnDetail('Status', $summary['status']);
        $this->components->twoColumnDetail('Kickoff (UTC)', $summary['kickoffAt']);
        $this->components->twoColumnDetail('WAT date', $summary['watDate']);
        $this->components->twoColumnDetail(
            'Teams',
            ($summary['homeTeam'] ?? 'TBD').' vs '.($summary['awayTeam'] ?? 'TBD'),
        );
        $this->components->twoColumnDetail(
            'Codes',
            ($summary['homeCode'] ?? '—').' vs '.($summary['awayCode'] ?? '—'),
        );
        $this->components->twoColumnDetail(
            'Venue',
            trim(($summary['stadium'] ?? 'TBD').' · '.($summary['city'] ?? '')),
        );

        if ($summary['homeScore'] !== null && $summary['awayScore'] !== null) {
            $this->components->twoColumnDetail('Score', "{$summary['homeScore']}-{$summary['awayScore']}");
        }

        $this->components->twoColumnDetail('Markets attached', (string) $summary['marketsCount']);

        if ($summary['marketNames'] !== []) {
            $this->newLine();
            $this->line('Markets:');

            foreach ($summary['marketNames'] as $marketName) {
                $this->line("  - {$marketName}");
            }
        }

        return self::SUCCESS;
    }
}
