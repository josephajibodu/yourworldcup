<?php

namespace App\Listeners;

use App\Referrals\ReferralService;
use Illuminate\Auth\Events\Verified;

class AttemptReferralCredit
{
    public function __construct(private ReferralService $referrals) {}

    public function handle(Verified $event): void
    {
        $this->referrals->attemptCredit($event->user);
    }
}
