<?php

namespace App\Predictions\Importing;

use App\Bracket\BracketSlotImporter;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\PredictionMarket;
use App\Models\Stadium;
use App\Models\Team;
use Illuminate\Support\Carbon;

/**
 * Imports the World Cup teams, stadiums, and fixtures from the JSON exports in
 * database/data and attaches every enabled prediction market to each fixture.
 *
 * Idempotent: keyed on each record's source id, so it can be re-run to pick up
 * played results without creating duplicates.
 */
class WorldCupImporter
{
    /** Stadium source id => IANA timezone, used to turn venue-local kickoff into UTC. */
    private const STADIUM_TIMEZONES = [
        '1' => 'America/Mexico_City',   // Estadio Azteca, Mexico City
        '2' => 'America/Mexico_City',   // Estadio Akron, Guadalajara
        '3' => 'America/Monterrey',     // Estadio BBVA, Monterrey
        '4' => 'America/Chicago',       // AT&T Stadium, Dallas
        '5' => 'America/Chicago',       // NRG Stadium, Houston
        '6' => 'America/Chicago',       // Arrowhead, Kansas City
        '7' => 'America/New_York',      // Mercedes-Benz, Atlanta
        '8' => 'America/New_York',      // Hard Rock, Miami
        '9' => 'America/New_York',      // Gillette, Boston
        '10' => 'America/New_York',     // Lincoln Financial, Philadelphia
        '11' => 'America/New_York',     // MetLife, New York/New Jersey
        '12' => 'America/Toronto',      // BMO Field, Toronto
        '13' => 'America/Vancouver',    // BC Place, Vancouver
        '14' => 'America/Los_Angeles',  // Lumen Field, Seattle
        '15' => 'America/Los_Angeles',  // Levi's, San Francisco Bay Area
        '16' => 'America/Los_Angeles',  // SoFi, Los Angeles
    ];

    private const TYPE_TO_STAGE = [
        'group' => FixtureStage::Group,
        'r32' => FixtureStage::Round32,
        'r16' => FixtureStage::Round16,
        'qf' => FixtureStage::QuarterFinal,
        'sf' => FixtureStage::SemiFinal,
        'third' => FixtureStage::ThirdPlace,
        'final' => FixtureStage::Final,
    ];

    /** ISO 3166-1 alpha-2 codes for African nations — drives the is_african filter. */
    private const AFRICAN_ISO2 = [
        'DZ', 'AO', 'BJ', 'BW', 'BF', 'BI', 'CV', 'CM', 'CF', 'TD', 'KM', 'CG', 'CD', 'CI',
        'DJ', 'EG', 'GQ', 'ER', 'SZ', 'ET', 'GA', 'GM', 'GH', 'GN', 'GW', 'KE', 'LS', 'LR',
        'LY', 'MG', 'MW', 'ML', 'MR', 'MU', 'MA', 'MZ', 'NA', 'NE', 'NG', 'RW', 'ST', 'SN',
        'SC', 'SL', 'SO', 'ZA', 'SS', 'SD', 'TZ', 'TG', 'TN', 'UG', 'ZM', 'ZW',
    ];

    public function __construct(private readonly string $dataPath) {}

    public static function fromDefaultPath(): self
    {
        return new self(database_path('data'));
    }

    public function import(): void
    {
        $this->importStadiums();
        $this->importTeams();
        $this->importFixtures();
        $this->attachEnabledMarkets();
        BracketSlotImporter::fromDefaultPath()->import();
    }

    public function importStadiums(): void
    {
        foreach ($this->read('football.stadiums.json') as $row) {
            $externalId = (string) $row['id'];

            Stadium::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'name' => $row['name_en'],
                    'fifa_name' => $row['fifa_name'] ?? null,
                    'city' => $row['city_en'] ?? null,
                    'country' => $row['country_en'] ?? null,
                    'capacity' => isset($row['capacity']) ? (int) $row['capacity'] : null,
                    'region' => $row['region'] ?? null,
                    'timezone' => self::STADIUM_TIMEZONES[$externalId] ?? null,
                ],
            );
        }
    }

    public function importTeams(): void
    {
        foreach ($this->read('football.teams.json') as $row) {
            $iso2 = strtoupper((string) ($row['iso2'] ?? ''));

            Team::updateOrCreate(
                ['external_id' => (string) $row['id']],
                [
                    'name' => $row['name_en'],
                    'code' => $row['fifa_code'],
                    'iso2' => $row['iso2'] ?? null,
                    'group_code' => $row['groups'] ?? null,
                    'is_african' => in_array($iso2, self::AFRICAN_ISO2, true),
                    'flag' => $row['flag'] ?? null,
                ],
            );
        }
    }

    public function importFixtures(): void
    {
        $teamsByExternalId = Team::query()->pluck('id', 'external_id');
        $stadiumsByExternalId = Stadium::query()->pluck('id', 'external_id');

        foreach ($this->read('football.matches.json') as $row) {
            $stadiumExternalId = (string) ($row['stadium_id'] ?? '');
            $timezone = self::STADIUM_TIMEZONES[$stadiumExternalId] ?? 'UTC';
            $kickoff = Carbon::createFromFormat('m/d/Y H:i', $row['local_date'], $timezone)->utc();

            $stage = self::TYPE_TO_STAGE[$row['type']] ?? FixtureStage::Group;
            $homeId = $teamsByExternalId[(string) ($row['home_team_id'] ?? '')] ?? null;
            $awayId = $teamsByExternalId[(string) ($row['away_team_id'] ?? '')] ?? null;
            $finished = strtoupper((string) ($row['finished'] ?? 'FALSE')) === 'TRUE';

            $homeScore = $finished ? (int) $row['home_score'] : null;
            $awayScore = $finished ? (int) $row['away_score'] : null;
            $winnerId = null;
            if ($finished && $homeId !== null && $awayId !== null && $homeScore !== $awayScore) {
                $winnerId = $homeScore > $awayScore ? $homeId : $awayId;
            }

            Fixture::updateOrCreate(
                ['external_id' => (string) $row['id']],
                [
                    'stage' => $stage,
                    'group_code' => $stage === FixtureStage::Group ? ($row['group'] ?? null) : null,
                    'matchday' => isset($row['matchday']) ? (int) $row['matchday'] : null,
                    'home_team_id' => $homeId,
                    'away_team_id' => $awayId,
                    'stadium_id' => $stadiumsByExternalId[$stadiumExternalId] ?? null,
                    'kickoff_at' => $kickoff,
                    'lock_at' => $kickoff->copy()->subHour(),
                    'status' => $finished ? FixtureStatus::Final : FixtureStatus::Scheduled,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'winner_team_id' => $winnerId,
                ],
            );
        }
    }

    public function attachEnabledMarkets(): void
    {
        $markets = PredictionMarket::query()->enabled()->get();

        if ($markets->isEmpty()) {
            return;
        }

        Fixture::query()->each(function (Fixture $fixture) use ($markets): void {
            foreach ($markets as $market) {
                FixtureMarket::firstOrCreate([
                    'fixture_id' => $fixture->id,
                    'prediction_market_id' => $market->id,
                ]);
            }
        });
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
