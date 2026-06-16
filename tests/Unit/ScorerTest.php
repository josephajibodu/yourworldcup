<?php

use App\Predictions\Scoring\ExactScoreScorer;
use App\Predictions\Scoring\MatchWinnerScorer;
use App\Predictions\Scoring\ScorerRegistry;

describe('MatchWinnerScorer', function () {
    it('awards base points for the correct pick', function () {
        expect((new MatchWinnerScorer)->score(['selected' => 'home'], ['winner' => 'home'], 1))->toBe(1);
    });

    it('awards nothing for a wrong pick', function () {
        expect((new MatchWinnerScorer)->score(['selected' => 'away'], ['winner' => 'home'], 1))->toBe(0);
    });

    it('scores a draw', function () {
        expect((new MatchWinnerScorer)->score(['selected' => 'draw'], ['winner' => 'draw'], 1))->toBe(1);
    });

    it('returns zero when data is missing', function () {
        expect((new MatchWinnerScorer)->score([], ['winner' => 'home'], 1))->toBe(0)
            ->and((new MatchWinnerScorer)->score(['selected' => 'home'], [], 1))->toBe(0);
    });
});

describe('ExactScoreScorer', function () {
    it('awards base points only for the exact score', function () {
        expect((new ExactScoreScorer)->score(['home' => 2, 'away' => 1], ['home' => 2, 'away' => 1], 3))->toBe(3);
    });

    it('awards nothing for the right winner but wrong score', function () {
        expect((new ExactScoreScorer)->score(['home' => 3, 'away' => 0], ['home' => 2, 'away' => 1], 3))->toBe(0);
    });

    it('returns zero when a score is missing', function () {
        expect((new ExactScoreScorer)->score(['home' => 2], ['home' => 2, 'away' => 1], 3))->toBe(0);
    });
});

describe('ScorerRegistry', function () {
    it('resolves a registered scorer', function () {
        $registry = new ScorerRegistry;
        $scorer = new MatchWinnerScorer;
        $registry->register('match_winner', $scorer);

        expect($registry->resolve('match_winner'))->toBe($scorer)
            ->and($registry->has('match_winner'))->toBeTrue()
            ->and($registry->has('nope'))->toBeFalse();
    });

    it('throws for an unknown key', function () {
        (new ScorerRegistry)->resolve('nope');
    })->throws(InvalidArgumentException::class);
});
