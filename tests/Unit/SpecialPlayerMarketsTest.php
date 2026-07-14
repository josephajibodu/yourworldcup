<?php

use App\Predictions\Markets\M101PlayerMarkets;
use App\Predictions\Markets\M102PlayerMarkets;
use App\Predictions\Markets\SpecialPlayerMarkets;

it('returns all special player market keys', function () {
    expect(SpecialPlayerMarkets::keys())->toBe(array_merge(
        M101PlayerMarkets::keys(),
        M102PlayerMarkets::keys(),
    ));
});

it('returns definitions for supported match numbers only', function () {
    expect(SpecialPlayerMarkets::definitionsForMatch('101'))->toHaveCount(7)
        ->and(SpecialPlayerMarkets::definitionsForMatch('102'))->toHaveCount(9)
        ->and(SpecialPlayerMarkets::definitionsForMatch('103'))->toBe([]);
});
