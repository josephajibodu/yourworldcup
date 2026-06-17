<?php

namespace App\Console\Commands;

use App\Fixtures\FixtureResultRecorder;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class SetFixtureResultCommand extends Command
{
    protected $signature = 'fixture:result
                            {fixture? : Tournament match id (external id, e.g. 73)}
                            {--json= : Bulk JSON array of results}
                            {--file= : Path to a JSON file of results}
                            {--settle : Settle predictions for updated fixtures when finished}';

    protected $description = 'Record final scores for one or more fixtures';

    public function handle(FixtureResultRecorder $recorder): int
    {
        if ($json = $this->option('json')) {
            return $this->recordBulk($recorder, (string) $json);
        }

        if ($file = $this->option('file')) {
            $path = (string) $file;

            if (! is_readable($path)) {
                $this->error("Cannot read file [{$path}].");

                return self::FAILURE;
            }

            $contents = file_get_contents($path);

            if ($contents === false) {
                $this->error("Cannot read file [{$path}].");

                return self::FAILURE;
            }

            return $this->recordBulk($recorder, $contents);
        }

        $fixtureId = $this->argument('fixture');

        if ($fixtureId === null) {
            $this->line('Pass a match id, or use --json / --file for bulk results.');
            $this->line('Example: php artisan fixture:result 73');

            return self::FAILURE;
        }

        return $this->recordInteractive($recorder, (string) $fixtureId);
    }

    private function recordInteractive(FixtureResultRecorder $recorder, string $fixtureId): int
    {
        try {
            $fixture = $recorder->findByExternalId($fixtureId);
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($recorder->describe($fixture));

        $homeScore = (int) $this->ask('Home score');
        $awayScore = (int) $this->ask('Away score');

        try {
            $updated = $recorder->record($fixture, $homeScore, $awayScore);
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail(
            $recorder->describe($updated),
            "{$homeScore}-{$awayScore}",
        );

        if ($this->option('settle')) {
            $this->call('predictions:settle');
        }

        return self::SUCCESS;
    }

    private function recordBulk(FixtureResultRecorder $recorder, string $json): int
    {
        try {
            $entries = $recorder->parseBulkInput($json);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                $this->error(is_array($messages) ? $messages[0] : (string) $messages);
            }

            return self::FAILURE;
        }

        if ($entries === []) {
            $this->warn('No results in bulk payload.');

            return self::SUCCESS;
        }

        $recorded = 0;

        foreach ($entries as $entry) {
            try {
                $fixture = $recorder->findByExternalId($entry['id']);
                $updated = $recorder->record($fixture, $entry['home'], $entry['away']);
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $messages) {
                    $this->error("M{$entry['id']}: ".(is_array($messages) ? $messages[0] : (string) $messages));
                }

                continue;
            }

            $this->components->twoColumnDetail(
                $recorder->describe($updated),
                "{$entry['home']}-{$entry['away']}",
            );

            $recorded++;
        }

        $this->info("Recorded {$recorded} of ".count($entries).' fixture result(s).');

        if ($this->option('settle') && $recorded > 0) {
            $this->call('predictions:settle');
        }

        return $recorded === count($entries) ? self::SUCCESS : self::FAILURE;
    }
}
