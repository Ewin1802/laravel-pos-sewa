<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'device_uid' => ['required', 'string', 'max:255'],
            'payment_method' => [
                'nullable',
                'string',
                Rule::in([
                    Invoice::PAYMENT_METHOD_MANUAL_BANK,
                    Invoice::PAYMENT_METHOD_MANUAL_QRIS,
                    Invoice::PAYMENT_METHOD_OTHER,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Plan selection is required',
            'plan_id.integer' => 'Plan ID must be a valid number',
            'plan_id.exists' => 'Selected plan does not exist',
            'device_uid.required' => 'Device UID is required',
            'device_uid.string' => 'Device UID must be a string',
            'device_uid.max' => 'Device UID cannot exceed 255 characters',
            'payment_method.in' => 'Invalid payment method selected',
        ];
    }
}
