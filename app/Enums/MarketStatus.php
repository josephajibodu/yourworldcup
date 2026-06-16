<?php

namespace App\Enums;

enum MarketStatus: string
{
    case Open = 'open';
    case Locked = 'locked';
    case Settled = 'settled';
    case Void = 'void';
}
