<?php

namespace App\Bracket;

use App\Enums\BracketSlotType;
use InvalidArgumentException;

class BracketSlotLabelParser
{
    /**
     * @return array{type: BracketSlotType, spec: array<string, mixed>, label: string}
     */
    public function parse(string $label): array
    {
        $label = trim($label);

        if (preg_match('/^Winner Group ([A-L])$/i', $label, $matches)) {
            return $this->groupSlot(BracketSlotType::GroupWinner, strtoupper($matches[1]), $label);
        }

        if (preg_match('/^Runner-up Group ([A-L])$/i', $label, $matches)) {
            return $this->groupSlot(BracketSlotType::GroupRunnerUp, strtoupper($matches[1]), $label);
        }

        if (preg_match('/^3rd Group ([A-L](?:\/[A-L])+)$/i', $label, $matches)) {
            $groups = array_map(
                strtoupper(...),
                explode('/', $matches[1]),
            );

            return [
                'type' => BracketSlotType::BestThird,
                'spec' => ['groups' => $groups],
                'label' => $label,
            ];
        }

        if (preg_match('/^Winner Match (\d+)$/i', $label, $matches)) {
            return $this->matchSlot(BracketSlotType::KnockoutWinner, $matches[1], $label);
        }

        if (preg_match('/^Loser Match (\d+)$/i', $label, $matches)) {
            return $this->matchSlot(BracketSlotType::KnockoutLoser, $matches[1], $label);
        }

        throw new InvalidArgumentException("Unrecognised bracket slot label [{$label}].");
    }

    /**
     * @return array{type: BracketSlotType, spec: array<string, mixed>, label: string}
     */
    private function groupSlot(BracketSlotType $type, string $group, string $label): array
    {
        return [
            'type' => $type,
            'spec' => ['group' => $group],
            'label' => $label,
        ];
    }

    /**
     * @return array{type: BracketSlotType, spec: array<string, mixed>, label: string}
     */
    private function matchSlot(BracketSlotType $type, string $matchId, string $label): array
    {
        return [
            'type' => $type,
            'spec' => ['match' => $matchId],
            'label' => $label,
        ];
    }
}
