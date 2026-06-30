<?php

namespace App\Enums;

enum ResultDuration: string
{
    case Regular = 'regular';
    case ExtraTime = 'extra_time';
    case Penalties = 'penalties';

    public function statusLabel(): string
    {
        return match ($this) {
            self::Regular => 'FT',
            self::ExtraTime => 'AET',
            self::Penalties => 'PEN',
        };
    }

    public static function fromProvider(?string $duration): ?self
    {
        return match (strtoupper((string) $duration)) {
            'REGULAR' => self::Regular,
            'EXTRA_TIME', 'EXTRATIME' => self::ExtraTime,
            'PENALTY_SHOOTOUT' => self::Penalties,
            default => null,
        };
    }
}
