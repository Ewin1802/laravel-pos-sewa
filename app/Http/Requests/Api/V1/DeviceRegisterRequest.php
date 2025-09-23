<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DeviceRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_uid' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_uid.required' => 'Device UID is required',
            'device_uid.string' => 'Device UID must be a string',
            'device_uid.max' => 'Device UID cannot exceed 255 characters',
            'label.string' => 'Device label must be a string',
            'label.max' => 'Device label cannot exceed 255 characters',
        ];
    }
}
