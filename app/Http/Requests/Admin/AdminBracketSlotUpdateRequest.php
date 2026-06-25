<?php

namespace App\Http\Requests\Admin;

use App\Bracket\BracketSlotEligibility;
use App\Models\BracketSlot;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminBracketSlotUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $teamId = $this->input('team_id');

            if ($teamId === null) {
                return;
            }

            /** @var BracketSlot $slot */
            $slot = $this->route('bracketSlot');
            $team = Team::query()->findOrFail($teamId);

            if (! app(BracketSlotEligibility::class)->isEligible($slot, $team)) {
                $validator->errors()->add('team_id', __('The selected team is not eligible for this slot.'));
            }
        });
    }

    public function team(): ?Team
    {
        $teamId = $this->input('team_id');

        if ($teamId === null) {
            return null;
        }

        return Team::query()->find($teamId);
    }
}
