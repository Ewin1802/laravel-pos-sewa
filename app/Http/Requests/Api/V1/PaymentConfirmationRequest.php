<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'evidence_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,heic', 'max:5120'], // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required' => 'Invoice ID is required',
            'invoice_id.integer' => 'Invoice ID must be a valid number',
            'invoice_id.exists' => 'Invoice not found',
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a valid number',
            'amount.min' => 'Payment amount must be greater than zero',
            'bank_name.string' => 'Bank name must be a string',
            'bank_name.max' => 'Bank name cannot exceed 255 characters',
            'reference_no.string' => 'Reference number must be a string',
            'reference_no.max' => 'Reference number cannot exceed 255 characters',
            'notes.string' => 'Notes must be a string',
            'notes.max' => 'Notes cannot exceed 1000 characters',
            'evidence_file.required' => 'Payment evidence file is required',
            'evidence_file.file' => 'Evidence must be a valid file',
            'evidence_file.mimes' => 'Evidence file must be jpg, jpeg, png, pdf, or heic',
            'evidence_file.max' => 'Evidence file cannot exceed 5MB',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
