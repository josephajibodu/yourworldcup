<?php

namespace App\Enums;

enum MarketInputType: string
{
    case SingleSelect = 'single_select';
    case Boolean = 'boolean';
    case Scoreline = 'scoreline';
    case Integer = 'integer';
}
