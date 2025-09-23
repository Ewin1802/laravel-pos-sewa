<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LicenseIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'integer', 'exists:devices,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'Device ID is required',
            'device_id.integer' => 'Device ID must be a valid number',
            'device_id.exists' => 'Device not found',
        ];
    }
}
