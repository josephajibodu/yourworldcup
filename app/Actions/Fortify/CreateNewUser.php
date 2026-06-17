<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        if ($referrerId = $this->resolveReferrerId()) {
            $user->forceFill(['referred_by_id' => $referrerId])->save();
        }

        return $user;
    }

    private function resolveReferrerId(): ?int
    {
        $code = session()->pull('referral_code');

        if (! is_string($code) || $code === '') {
            return null;
        }

        $referrer = User::query()->where('referral_code', $code)->first();

        return $referrer?->id;
    }
}
