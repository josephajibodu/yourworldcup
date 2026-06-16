<?php

namespace App\Enums;

enum FixtureStatus: string
{
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Final = 'final';
    case Void = 'void';

    public function isComplete(): bool
    {
        return $this === self::Final;
    }
}
