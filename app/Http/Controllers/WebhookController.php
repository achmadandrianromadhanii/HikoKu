<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function midtrans(Request $request): JsonResponse
    {
        $payload = $request->all();

        $orderId = (string) data_get($payload, 'order_id', '');
        $statusCode = (string) data_get($payload, 'status_code', '');
        $grossAmount = (string) data_get($payload, 'gross_amount', '');
        $signatureKey = (string) data_get($payload, 'signature_key', '');
        $serverKey = (string) config('services.midtrans.server_key', '');

        if (
            $orderId === '' ||
            $statusCode === '' ||
            $grossAmount === '' ||
            $signatureKey === '' ||
            $serverKey === ''
        ) {
            Log::warning('WebhookController::midtrans missing required payload', [
                'order_id' => $orderId,
                'status_code' => $statusCode,
                'gross_amount' => $grossAmount,
                'has_signature_key' => $signatureKey !== '',
                'has_server_key' => $serverKey !== '',
            ]);

            return response()->json(['message' => 'Invalid payload'], 422);
        }

        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $serverKey
        );

        if (! hash_equals($expectedSignature, $signatureKey)) {
            Log::warning('WebhookController::midtrans invalid signature', [
                'order_id' => $orderId,
            ]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        try {
            $payment = $this->paymentService->findByOrderId($orderId);

            if (! $payment) {
                Log::warning('WebhookController::midtrans payment not found', [
                    'order_id' => $orderId,
                    'payload' => $payload,
                ]);

                return response()->json(['message' => 'Payment not found'], 404);
            }

            $mappedStatus = $this->paymentService->mapMidtransStatus(
                (string) data_get($payload, 'transaction_status', ''),
                data_get($payload, 'fraud_status')
            );

            $currentStatus = (string) ($payment->status ?? '');

            /*
            |--------------------------------------------------------------------------
            | Idempotent guard
            |--------------------------------------------------------------------------
            | Jika Midtrans mengirim webhook status yang sama berulang kali,
            | jangan proses ulang agar status tidak berantakan.
            */
            if ($currentStatus !== '' && $currentStatus === $mappedStatus) {
                return response()->json(['message' => 'Already processed'], 200);
            }

            $this->paymentService->confirmPayment(
                payment: $payment,
                mappedStatus: $mappedStatus,
                gatewayTransactionId: (string) data_get($payload, 'transaction_id', $orderId),
                notes: $this->encodePayload($payload),
            );

            return response()->json(['message' => 'OK'], 200);
        } catch (\Throwable $e) {
            Log::error('WebhookController::midtrans failed', [
                'message' => $e->getMessage(),
                'order_id' => $orderId,
                'payload' => $payload,
            ]);

            return response()->json(['message' => 'Server error'], 500);
        }
    }

    protected function encodePayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json !== false ? $json : '{}';
    }
}
