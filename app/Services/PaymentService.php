<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected MidtransService $midtransService,
        protected RentalService $rentalService,
    ) {}

    public function createPayment(Rental $rental, bool $isRetry = false): Payment
    {
        try {
            return DB::transaction(function () use ($rental, $isRetry) {
                $rentalQuery = Rental::query()->lockForUpdate();

                if (method_exists(new Rental(), 'payment')) {
                    $rentalQuery->with('payment');
                }

                if (method_exists(new Rental(), 'payments')) {
                    $rentalQuery->with('payments');
                }

                $rental = $rentalQuery->findOrFail($rental->id);

                if ($rental->status !== 'pending_payment') {
                    throw ValidationException::withMessages([
                        'payment' => 'Rental tidak bisa dibuatkan pembayaran dari status saat ini.',
                    ]);
                }

                if (! $isRetry) {
                    $existingPending = Payment::query()
                        ->where('rental_id', $rental->id)
                        ->where('status', 'pending')
                        ->lockForUpdate()
                        ->latest('id')
                        ->first();

                    if ($existingPending) {
                        if ($existingPending->snap_token && $existingPending->payment_url) {
                            return $existingPending;
                        }

                        $existingOrderId = (string) ($existingPending->gateway_transaction_id ?: $existingPending->payment_code);

                        if ($existingOrderId === '') {
                            $existingOrderId = $existingPending->payment_code ?: ('PAY-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8)));
                        }

                        $snap = $this->midtransService->createSnapToken($rental, $existingOrderId);

                        $existingPending->update([
                            'gateway_transaction_id' => $existingOrderId,
                            'snap_token' => $snap['snap_token'] ?? $existingPending->snap_token,
                            'payment_url' => $snap['payment_url'] ?? $existingPending->payment_url,
                        ]);

                        return $existingPending->refresh();
                    }
                }

                $retryCount = Payment::query()
                    ->where('rental_id', $rental->id)
                    ->count();

                if ($retryCount >= 4) {
                    $rental->update([
                        'status' => 'cancelled',
                    ]);

                    throw ValidationException::withMessages([
                        'payment' => 'Batas retry pembayaran sudah habis. Rental dibatalkan.',
                    ]);
                }

                $paymentCode = 'PAY-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));

                $orderId = $paymentCode;
                if ($isRetry && $retryCount > 0) {
                    $orderId .= '-retry-' . $retryCount;
                }

                $paymentPayload = [
                    'rental_id' => $rental->id,
                    'payment_code' => $paymentCode,
                    'amount' => $rental->grand_total,
                    'status' => 'pending',
                ];

                $paymentMethodColumn = $this->getPaymentMethodColumn();
                $paymentPayload[$paymentMethodColumn] = 'midtrans';

                $payment = Payment::create($paymentPayload);

                $snap = $this->midtransService->createSnapToken($rental, $orderId);

                $payment->update([
                    'gateway_transaction_id' => $orderId,
                    'snap_token' => $snap['snap_token'] ?? null,
                    'payment_url' => $snap['payment_url'] ?? null,
                ]);

                return $payment->refresh();
            });
        } catch (\Throwable $e) {
            Log::channel('app_error')->error('PaymentService::createPayment failed', [
                'rental_id' => $rental->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function confirmPayment(
        Payment $payment,
        string $mappedStatus,
        ?string $gatewayTransactionId = null,
        ?string $notes = null
    ): Payment {
        try {
            return DB::transaction(function () use ($payment, $mappedStatus, $gatewayTransactionId, $notes) {
                $payment = Payment::query()
                    ->lockForUpdate()
                    ->with('rental')
                    ->findOrFail($payment->id);

                $currentStatus = (string) ($payment->status ?? '');

                /*
                |--------------------------------------------------------------------------
                | Idempotent / safe transition
                |--------------------------------------------------------------------------
                */

                if ($currentStatus !== '' && $currentStatus === $mappedStatus) {
                    return $payment;
                }

                if ($currentStatus === 'paid' && $mappedStatus === 'pending') {
                    return $payment;
                }

                $payment->update([
                    'gateway_transaction_id' => $gatewayTransactionId ?: $payment->gateway_transaction_id,
                    'status' => $mappedStatus,
                    'paid_at' => $mappedStatus === 'paid'
                        ? ($payment->paid_at ?: now())
                        : $payment->paid_at,
                    'notes' => $notes ?: $payment->notes,
                ]);

                if (
                    $mappedStatus === 'paid' &&
                    $payment->rental &&
                    $payment->rental->status === 'pending_payment'
                ) {
                    $this->rentalService->confirmRental($payment->rental);
                }

                return $payment->refresh();
            });
        } catch (\Throwable $e) {
            Log::channel('app_error')->error('PaymentService::confirmPayment failed', [
                'payment_id' => $payment->id,
                'mapped_status' => $mappedStatus,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function mapMidtransStatus(string $transactionStatus, ?string $fraudStatus = null): string
    {
        return match ($transactionStatus) {
            'settlement' => 'paid',
            'capture' => $fraudStatus === 'challenge' ? 'pending' : 'paid',
            'pending' => 'pending',
            'deny', 'cancel', 'failure' => 'failed',
            'expire' => 'expired',
            'refund', 'partial_refund' => 'refunded',
            default => 'failed',
        };
    }

    public function findByOrderId(string $orderId): ?Payment
    {
        return Payment::query()
            ->where('gateway_transaction_id', $orderId)
            ->orWhere('payment_code', $orderId)
            ->latest('id')
            ->first();
    }

    protected function getPaymentMethodColumn(): string
    {
        return Schema::hasColumn('payments', 'payment_method')
            ? 'payment_method'
            : 'method';
    }
}
