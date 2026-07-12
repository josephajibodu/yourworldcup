<?php

namespace App\Enums;

enum MobileNetwork: string
{
    case Mtn = 'mtn';
    case Airtel = 'airtel';
    case Glo = 'glo';
    case NineMobile = '9mobile';

    public function label(): string
    {
        return match ($this) {
            self::Mtn => 'MTN',
            self::Airtel => 'Airtel',
            self::Glo => 'Glo',
            self::NineMobile => '9mobile',
        };
    }
}
