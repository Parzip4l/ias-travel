<?php

namespace App\Services\Payment;

use App\Services\Payment\Model\Payment;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function createForSppd($sppd, $amount, $email)
    {
        $externalId = uniqid('SPPD-');
        
        $response = Http::withBasicAuth(env('XENDIT_SECRET_KEY'), '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $externalId,
                'amount'      => $amount,
                'payer_email' => $email,
                'description' => "Pembayaran SPPD #{$sppd->id}",
            ])
            ->throw()
            ->json();

        return Payment::create([
            'sppd_id'     => $sppd->id,
            'external_id' => $externalId,
            'invoice_id'  => $response['id'],
            'amount'      => $amount,
            'status'      => $response['status'],
            'payer_email' => $email,
            'invoice_url' => $response['invoice_url'],
            'raw_response'=> $response,
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
