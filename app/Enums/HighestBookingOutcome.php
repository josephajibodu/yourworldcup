<?php

namespace App\Enums;

enum HighestBookingOutcome: string
{
    case Home = 'home';
    case Draw = 'draw';
    case Away = 'away';
}
