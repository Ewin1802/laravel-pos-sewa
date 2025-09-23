<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\PaymentConfirmationRequest;
use App\Models\Invoice;
use App\Models\PaymentConfirmation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentConfirmationController extends BaseApiController
{
    public function store(PaymentConfirmationRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            if (!$merchant->isActive()) {
                return $this->errorResponse('Merchant account is not active', 403);
            }

            $validated = $request->validated();

            $invoice = Invoice::where('id', $validated['invoice_id'])
                ->where('merchant_id', $merchant->id)
                ->firstOrFail();

            if ($invoice->isPaid()) {
                return $this->errorResponse('Invoice is already paid', 422);
            }

            // Check if there's already a pending confirmation
            $existingConfirmation = PaymentConfirmation::where('invoice_id', $invoice->id)
                ->where('status', PaymentConfirmation::STATUS_SUBMITTED)
                ->first();

            if ($existingConfirmation) {
                return $this->errorResponse('Payment confirmation already submitted and pending review', 422);
            }

            $confirmation = DB::transaction(function () use ($validated, $invoice, $user, $request) {
                // Handle evidence file upload
                $evidencePath = null;
                if ($request->hasFile('evidence_file')) {
                    $evidencePath = $this->storeEvidenceFile($request->file('evidence_file'));
                }

                // Create payment confirmation
                $confirmation = PaymentConfirmation::create([
                    'invoice_id' => $invoice->id,
                    'submitted_by' => $user->name,
                    'amount' => $validated['amount'],
                    'bank_name' => $validated['bank_name'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'evidence_path' => $evidencePath,
                    'status' => PaymentConfirmation::STATUS_SUBMITTED,
                ]);

                // Update invoice status to awaiting confirmation
                $invoice->update(['status' => Invoice::STATUS_AWAITING_CONFIRMATION]);

                return $confirmation;
            });

            return $this->successResponse([
                'payment_confirmation' => $confirmation,
                'invoice' => $invoice->fresh(),
                'devices' => $merchant->devices()->where('is_active', true)->get(),
                'message' => 'Payment evidence submitted successfully. Please wait for admin verification.',
            ], 'Payment confirmation submitted successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Payment confirmation failed: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Store evidence file with hash-based filename
     */
    private function storeEvidenceFile($file): string
    {
        // Generate hash-based filename to prevent conflicts and maintain privacy
        $extension = $file->getClientOriginalExtension();
        $hash = hash('sha256', $file->getContent() . time());
        $filename = $hash . '.' . $extension;

        // Store in public disk under evidence directory
        $path = $file->storeAs('evidence', $filename, 'public');

        return $path;
    }

    /**
     * Get payment confirmation history for the merchant
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $confirmations = PaymentConfirmation::whereHas('invoice', function ($query) use ($merchant) {
                $query->where('merchant_id', $merchant->id);
            })
                ->with(['invoice.subscription.plan'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return $this->successResponse([
                'confirmations' => $confirmations->items(),
                'devices' => $merchant->devices()->where('is_active', true)->get(),
                'pagination' => [
                    'current_page' => $confirmations->currentPage(),
                    'total_pages' => $confirmations->lastPage(),
                    'total_items' => $confirmations->total(),
                    'per_page' => $confirmations->perPage(),
                ],
            ], 'Payment confirmations retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payment confirmations: ' . $e->getMessage(), 500);
        }
    }
}
