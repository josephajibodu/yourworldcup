<?php

namespace App\Http\Requests;

use App\Enums\MobileNetwork;
use App\Enums\RewardPreference;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeeklyRewardClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('passed_on')) {
            $merge['passed_on'] = $this->boolean('passed_on');
        }

        if ($this->has('phone_number')) {
            $merge['phone_number'] = preg_replace('/\D+/', '', (string) $this->input('phone_number')) ?: null;
        }

        if ($this->has('account_number')) {
            $merge['account_number'] = preg_replace('/\D+/', '', (string) $this->input('account_number')) ?: null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $claiming = ! $this->boolean('passed_on');

        return [
            'week_start' => ['required', 'date_format:Y-m-d'],
            'passed_on' => ['required', 'boolean'],
            'preference' => [
                Rule::requiredIf($claiming),
                'nullable',
                Rule::enum(RewardPreference::class),
            ],
            'phone_number' => [
                Rule::requiredIf($claiming && $this->prefersMobileTopUp()),
                'nullable',
                'digits_between:10,11',
            ],
            'mobile_network' => [
                Rule::requiredIf($claiming && $this->prefersMobileTopUp()),
                'nullable',
                Rule::enum(MobileNetwork::class),
            ],
            'account_holder_name' => [
                Rule::requiredIf($claiming && $this->prefersCash()),
                'nullable',
                'string',
                'max:120',
            ],
            'bank_name' => [
                Rule::requiredIf($claiming && $this->prefersCash()),
                'nullable',
                'string',
                'max:120',
            ],
            'account_number' => [
                Rule::requiredIf($claiming && $this->prefersCash()),
                'nullable',
                'digits_between:10,10',
            ],
            'pass_on_message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preference.required' => 'Choose airtime, data, or cash when claiming your reward.',
            'phone_number.required' => 'Enter the phone number for your top-up.',
            'phone_number.digits_between' => 'Enter a valid Nigerian phone number.',
            'mobile_network.required' => 'Select your mobile network.',
            'account_holder_name.required' => 'Enter the name on the bank account.',
            'bank_name.required' => 'Enter your bank name.',
            'account_number.required' => 'Enter your account number.',
            'account_number.digits_between' => 'Enter a valid 10-digit account number.',
        ];
    }

    private function prefersMobileTopUp(): bool
    {
        return in_array($this->input('preference'), [
            RewardPreference::Airtime->value,
            RewardPreference::Data->value,
        ], true);
    }

    private function prefersCash(): bool
    {
        return $this->input('preference') === RewardPreference::Cash->value;
    }
}
