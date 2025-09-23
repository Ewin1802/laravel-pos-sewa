<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class TrialStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_uid' => ['required', 'string', 'max:255'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'trial_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_uid.required' => 'Device UID is required',
            'device_uid.string' => 'Device UID must be a string',
            'device_uid.max' => 'Device UID cannot exceed 255 characters',
            'plan_id.integer' => 'Plan ID must be a valid number',
            'plan_id.exists' => 'Selected plan does not exist',
            'trial_days.integer' => 'Trial days must be a valid number',
            'trial_days.min' => 'Trial days must be at least 1',
            'trial_days.max' => 'Trial days cannot exceed 30',
        ];
    }
}
