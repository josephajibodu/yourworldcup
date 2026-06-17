<?php

namespace App\Enums;

enum BracketSlotType: string
{
    case GroupWinner = 'group_winner';
    case GroupRunnerUp = 'group_runner_up';
    case BestThird = 'best_third';
    case KnockoutWinner = 'knockout_winner';
    case KnockoutLoser = 'knockout_loser';

    public function isGroupDerived(): bool
    {
        return match ($this) {
            self::GroupWinner, self::GroupRunnerUp, self::BestThird => true,
            self::KnockoutWinner, self::KnockoutLoser => false,
        };
    }
}
