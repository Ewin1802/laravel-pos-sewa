<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * TimeController handles server time requests with HMAC signature
 */
class TimeController extends BaseApiController
{
    /**
     * GET /api/v1/time
     *
     * Returns server time in epoch milliseconds with HMAC signature
     * for client time synchronization and anti-tampering validation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function now(Request $request): JsonResponse
    {
        $ms = (int) round(microtime(true) * 1000);

        $secret = config('license.secret');
        if (empty($secret)) {
            return $this->errorResponse('License secret is not configured', 500);
        }

        // payload yang ditandatangani cukup angka epoch ms agar klien mudah verifikasi
        $payload = (string) $ms;
        $rawSig = hash_hmac(config('license.time_sig_algo', 'sha256'), $payload, $secret, true);
        $sig = base64_encode($rawSig);

        return $this->successResponse([
            'server_epoch_ms' => $ms,
            'sig' => $sig,
        ]);
    }
}