<?php

use App\Predictions\Scoring\BooleanAnswerScorer;
use App\Predictions\Scoring\SelectedOptionScorer;

describe('BooleanAnswerScorer', function () {
    it('awards base points when the boolean answer matches', function () {
        expect((new BooleanAnswerScorer)->score(['answer' => true], ['answer' => true], 1))->toBe(1)
            ->and((new BooleanAnswerScorer)->score(['answer' => false], ['answer' => false], 2))->toBe(2);
    });

    it('awards nothing for a wrong answer', function () {
        expect((new BooleanAnswerScorer)->score(['answer' => true], ['answer' => false], 1))->toBe(0);
    });

    it('returns zero when data is missing', function () {
        expect((new BooleanAnswerScorer)->score([], ['answer' => true], 1))->toBe(0)
            ->and((new BooleanAnswerScorer)->score(['answer' => true], [], 1))->toBe(0);
    });
});

describe('SelectedOptionScorer', function () {
    it('awards base points for the correct option', function () {
        expect((new SelectedOptionScorer)->score(['selected' => 'home'], ['selected' => 'home'], 1))->toBe(1)
            ->and((new SelectedOptionScorer)->score(['selected' => 'none'], ['selected' => 'none'], 2))->toBe(2);
    });

    it('awards nothing for a wrong option', function () {
        expect((new SelectedOptionScorer)->score(['selected' => 'away'], ['selected' => 'home'], 1))->toBe(0);
    });

    it('returns zero when data is missing', function () {
        expect((new SelectedOptionScorer)->score([], ['selected' => 'home'], 1))->toBe(0)
            ->and((new SelectedOptionScorer)->score(['selected' => 'home'], [], 1))->toBe(0);
    });
});
