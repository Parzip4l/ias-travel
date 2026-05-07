<?php

namespace App\Services\Payment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payment\PaymentService;
use App\Services\Payment\Model\Payment;
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

    public function invoice($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $payment = Payment::with(['sppd.approvals'])->findOrFail($id);
        $canViewAll = $this->canViewAllSppdData($user);

        if (!$canViewAll) {
            $isOwner = (int) ($payment->sppd->user_id ?? 0) === (int) $user->id;
            $isApprover = $payment->sppd
                && $payment->sppd->approvals
                && $payment->sppd->approvals->contains(function ($approval) use ($user) {
                    return (int) $approval->approver_id === (int) $user->id;
                });

            if (!$isOwner && !$isApprover) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        if (empty($payment->invoice_url)) {
            return response()->json(['message' => 'Invoice belum tersedia untuk pembayaran ini.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $payment->id,
                'invoice_url' => $payment->invoice_url,
                'status' => $payment->status,
                'payment_type' => $payment->payment_type,
            ],
        ]);
    }

    private function canViewAllSppdData($user): bool
    {
        $role = strtolower((string) ($user->role ?? ''));

        if (in_array($role, ['admin', 'superadmin'], true)) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('superadmin');
        }

        return false;
    }

}
