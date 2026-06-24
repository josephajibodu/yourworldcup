<?php

use App\Support\TwitterHandle;

it('normalizes leading at signs', function () {
    expect(TwitterHandle::normalize('@joseph_ajibodu'))->toBe('joseph_ajibodu')
        ->and(TwitterHandle::normalize('  @player_one  '))->toBe('player_one');
});
