<?php

namespace App\Services\Payment;

use App\Services\Payment\Model\Payment;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function createForSppd($sppd, $amount, $email, $type = 'digital')
    {
        $externalId = uniqid('SPPD-');
        $response = null;

        if ($type === 'digital') {
            // Buat invoice Xendit
            $response = Http::withBasicAuth(env('XENDIT_SECRET_KEY'), '')
                ->post('https://api.xendit.co/v2/invoices', [
                    'external_id' => $externalId,
                    'amount'      => $amount,
                    'payer_email' => $email,
                    'description' => "Pembayaran SPPD #{$sppd->id}",
                ])
                ->throw()
                ->json();
        }

        return Payment::create([
            'sppd_id'     => $sppd->id,
            'external_id' => $externalId,
            'invoice_id'  => $response['id'] ?? null,
            'amount'      => $amount,
            'status'      => $type === 'digital'
                ? ($response['status'] ?? 'PENDING')
                : 'WAITING_INVOICE',
            'payer_email' => $email,
            'invoice_url' => $response['invoice_url'] ?? null,
            'raw_response'=> $response,
            'payment_type'=> $type,
        ]);
    }


    public function updateStatusFromWebhook(array $payload)
    {
        $invoiceId = $payload['id'] ?? null;
        $payment = Payment::where('invoice_id', $invoiceId)->first();

        if (!$payment) {
            \Log::error('Payment tidak ditemukan untuk invoice_id: ' . $invoiceId);
            return null;
        }

        $payment->status = $payload['status'] ?? 'FAILED';
        $payment->save();

        \Log::info('Payment updated via webhook: ' . $payment->invoice_id . ' Status: ' . $payment->status);

        return $payment;
    }

}
