<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Email</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; padding: 40px; border-radius: 8px;">
                    
                    <!-- LOGO -->
                    <tr>
                        <td align="center" style="padding-bottom: 20px;">
                            <img src="https://champoil.co.id/wp-content/uploads/2025/08/injourney-scaled.png" alt="InJourney" style="height: 50px; margin-right: 15px;">
                            <img src="https://champoil.co.id/wp-content/uploads/2025/08/ias-1-scaled.png" alt="IAS Support" style="height: 50px;">
                        </td>
                    </tr>

                    <!-- Title -->
                    <tr>
                        <td align="center" style="font-size: 20px; font-weight: bold;">
                            Verifikasi Email Anda
                        </td>
                    </tr>

                    <tr><td height="20"></td></tr>

                    <!-- Content -->
                    <tr>
                        <td>
                            <p>Halo {{ $user->name }},</p>
                            <p>Terima kasih telah mendaftar di <strong>IAS Travel</strong>.</p>
                            <p>Silakan klik tombol di bawah ini untuk memverifikasi email Anda. Link hanya berlaku selama <strong>5 menit</strong>:</p>
                            <p style="text-align: center; margin: 30px 0;">
                                <a href="{{ $url }}" style="background-color: #007bff; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px;">Verifikasi Sekarang</a>
                            </p>
                            <p>Jika Anda tidak merasa mendaftar, abaikan email ini.</p>
                            <p>Salam hangat,<br><strong>IAS Travel Team</strong></p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
