<?php

namespace App\Bracket;

use App\Enums\BracketSlotSide;
use App\Models\BracketSlot;
use App\Models\Fixture;
use Illuminate\Support\Facades\DB;

class BracketSlotImporter
{
    public function __construct(
        private readonly BracketSlotLabelParser $parser,
        private readonly string $dataPath,
    ) {}

    public static function fromDefaultPath(): self
    {
        return new self(
            app(BracketSlotLabelParser::class),
            database_path('data'),
        );
    }

    public function import(): void
    {
        $fixturesByExternalId = Fixture::query()
            ->whereNotNull('external_id')
            ->pluck('id', 'external_id');

        DB::transaction(function () use ($fixturesByExternalId): void {
            foreach ($this->read('football.matches.json') as $row) {
                $stage = $row['type'] ?? 'group';

                if ($stage === 'group') {
                    continue;
                }

                $fixtureId = $fixturesByExternalId[(string) ($row['id'] ?? '')] ?? null;

                if ($fixtureId === null) {
                    continue;
                }

                $this->importSide(
                    fixtureId: (int) $fixtureId,
                    side: BracketSlotSide::Home,
                    label: $row['home_team_label'] ?? null,
                    sourceFixtureId: $fixturesByExternalId[(string) ($row['home_team_id'] ?? '')] ?? null,
                );

                $this->importSide(
                    fixtureId: (int) $fixtureId,
                    side: BracketSlotSide::Away,
                    label: $row['away_team_label'] ?? null,
                    sourceFixtureId: $fixturesByExternalId[(string) ($row['away_team_id'] ?? '')] ?? null,
                );
            }
        });
    }

    private function importSide(
        int $fixtureId,
        BracketSlotSide $side,
        ?string $label,
        ?int $sourceFixtureId,
    ): void {
        if ($label === null || trim($label) === '') {
            return;
        }

        $parsed = $this->parser->parse($label);

        $existing = BracketSlot::query()
            ->where('feeds_fixture_id', $fixtureId)
            ->where('side', $side)
            ->first();

        BracketSlot::updateOrCreate(
            [
                'feeds_fixture_id' => $fixtureId,
                'side' => $side,
            ],
            [
                'label' => $parsed['label'],
                'slot_type' => $parsed['type'],
                'slot_spec' => $parsed['spec'],
                'source_fixture_id' => $sourceFixtureId,
                'resolved_team_id' => $existing?->resolved_team_id,
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function read(string $file): array
    {
        $path = $this->dataPath.'/'.$file;
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read World Cup data file [{$path}].");
        }

        return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }
}
