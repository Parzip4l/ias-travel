<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pembayaran SPPD Berhasil</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f6f8; padding: 20px; margin: 0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">

                    <!-- HEADER / LOGO -->
                    <tr>
                        <td align="center" style="padding-bottom: 25px;">
                            <img src="https://champoil.co.id/wp-content/uploads/2025/08/ias-1-scaled.png" alt="IAS Travel" style="height: 75px;">
                        </td>
                    </tr>

                    <!-- TITLE -->
                    <tr>
                        <td align="center" style="font-size: 22px; font-weight: bold; color: #333333; padding-bottom: 10px;">
                            💳 Pembayaran SPPD Berhasil
                        </td>
                    </tr>

                    <!-- CONTENT -->
                    <tr>
                        <td style="font-size: 15px; color: #555555; line-height: 1.6;">
                            <p>Halo <strong>{{ $payment->sppd->user->name }}</strong>,</p>
                            <p>Pembayaran untuk SPPD **#{{ $payment->sppd->nomor_sppd }}** telah berhasil.</p>
                            
                            <table width="100%" cellpadding="6" cellspacing="0" style="margin: 20px 0; border: 1px solid #e0e0e0; border-radius: 6px;">
                                <tr>
                                    <td style="font-weight: bold; width: 150px; background: #f9f9f9;">Nomor SPPD</td>
                                    <td>{{ $payment->sppd->nomor_sppd }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background: #f9f9f9;">Jumlah Bayar</td>
                                    <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background: #f9f9f9;">Status</td>
                                    <td>{{ strtoupper($payment->status) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background: #f9f9f9;">Invoice URL</td>
                                    <td><a href="{{ $payment->invoice_url }}" style="color: #007bff; text-decoration: none;">Klik untuk lihat</a></td>
                                </tr>
                            </table>

                            <p style="text-align: center; margin: 30px 0;">
                                <a href="{{ $payment->invoice_url }}" 
                                   style="background-color: #28a745; color: #ffffff; padding: 14px 28px; 
                                          text-decoration: none; font-size: 16px; border-radius: 8px; 
                                          display: inline-block;">
                                     Lihat Detail Pembayaran
                                </a>
                            </p>

                            <p>Terima kasih telah melakukan pembayaran.</p>
                            <p>Salam,<br><strong>IAS Travel Team</strong></p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td align="center" style="font-size: 12px; color: #999999; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                            Email ini dikirim otomatis oleh sistem IAS Travel.<br>
                            Jangan balas email ini.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
