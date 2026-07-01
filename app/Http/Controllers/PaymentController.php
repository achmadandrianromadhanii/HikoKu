<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function retry(Request $request, string $code)
    {
        $rental = Rental::query()
            ->with('payment')
            ->where('user_id', $request->user()->id)
            ->where('rental_code', $code)
            ->firstOrFail();

        if ($rental->status !== 'pending_payment') {
            return back()->with('error', 'Rental tidak bisa dibayar ulang.');
        }

        $this->paymentService->createPayment($rental, true);

        return redirect()->route('my-rentals.show', $rental->rental_code)
            ->with('success', 'Token pembayaran baru berhasil dibuat.');
    }

    public function success(Request $request)
    {
        // [KOMENTAR PENJELASAN]: Fallback Sync untuk Localhost.
        // Jika ada order_id dan transaction_status dari URL (dikirim oleh frontend MidtransSnap),
        // kita paksa sinkronisasi database karena Webhook Midtrans tidak bisa masuk ke localhost.
        $orderId = $request->query('order_id');
        $transactionStatus = $request->query('transaction_status');

        if ($orderId && $transactionStatus) {
            // [UPDATE]: Memperbaiki bug unknown column 'order_id'.
            // Di database, kolom yang menyimpan order id midtrans adalah gateway_transaction_id atau payment_code.
            // Maka kita gunakan fungsi findByOrderId yang sudah ada di PaymentService.
            $payment = $this->paymentService->findByOrderId($orderId);
            
            if ($payment && $payment->status !== 'paid') {
                $mappedStatus = $this->paymentService->mapMidtransStatus($transactionStatus);
                if ($mappedStatus === 'paid') {
                    $this->paymentService->confirmPayment($payment, 'paid');
                }
            }
        }

        return redirect()->route('home')->with('success', 'Pembayaran Berhasil! Silakan cek menu E-Ticket Saya.');
    }

    public function failed(): Response
    {
        return Inertia::render('Payment/Failed');
    }
}
