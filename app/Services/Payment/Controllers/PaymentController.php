<?php

namespace App\Services\Payment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payment\PaymentService;
use App\Services\Sppd\Model\Sppd;

class PaymentController extends Controller
{
    public function create(Request $request, PaymentService $paymentService)
    {
        $sppd = Sppd::findOrFail($request->sppd_id);

        $payment = $paymentService->createForSppd(
            $sppd,
            $request->amount ?? 10000,
            $request->email ?? $sppd->user->email,
            $request->payment_type ?? 'digital'
        );

        if ($payment->payment_type === 'digital') {
            return redirect($payment->invoice_url);
        }

        // Kalau reimbursement cukup kembali ke detail SPPD
        return redirect()->route('sppd.show', $sppd->id)
            ->with('success', 'Metode pembayaran Reimbursement telah dipilih. Menunggu invoice dari pihak travel.');
    }

    public function webhook(Request $request, PaymentService $paymentService)
    {
        $payload = $request->all();

        \Log::info('Webhook diterima: ' . json_encode($payload));

        $payment = $paymentService->updateStatusFromWebhook($payload);

        if ($payment && $payment->status === 'PAID') {
            try {
                \Mail::to($payment->payer_email)->send(new \App\Mail\PaymentSuccessful($payment));
                \Log::info('Email payment berhasil dikirim ke ' . $payment->payer_email);
            } catch (\Exception $e) {
                \Log::error('Gagal mengirim email payment: ' . $e->getMessage());
            }
        }

        return response()->json(['status' => 'ok']);
    }

}
