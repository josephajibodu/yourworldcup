<?php

namespace App\Enums;

enum FixtureStage: string
{
    case Group = 'group';
    case Round32 = 'r32';
    case Round16 = 'r16';
    case QuarterFinal = 'qf';
    case SemiFinal = 'sf';
    case ThirdPlace = 'third_place';
    case Final = 'final';

    public function isKnockout(): bool
    {
        return $this !== self::Group;
    }

    public function label(): string
    {
        return match ($this) {
            self::Group => 'Group stage',
            self::Round32 => 'Round of 32',
            self::Round16 => 'Round of 16',
            self::QuarterFinal => 'Quarter-final',
            self::SemiFinal => 'Semi-final',
            self::ThirdPlace => 'Third-place play-off',
            self::Final => 'Final',
        };
    }
}
