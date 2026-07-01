<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Verifikasi HikoKu</title>
    <!-- [KOMENTAR PENJELASAN]: Menggunakan inline CSS dan struktur tabel yang didukung dengan sangat baik oleh sebagian besar klien email. -->
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f7f6; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 40px auto 0; width: 100%; max-width: 600px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background: #0f6f9d; padding: 30px 20px; text-align: center; }
        .header img { height: 80px; margin-bottom: 15px; border-radius: 8px; object-fit: contain; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px; }
        .content { padding: 40px 30px; text-align: center; color: #334155; }
        .content p { font-size: 16px; line-height: 1.6; margin: 0 0 20px; color: #475569; }
        .otp-container { background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px 20px; margin: 30px auto; max-width: 300px; }
        .otp-code { font-size: 38px; font-weight: 800; letter-spacing: 12px; color: #0f6f9d; margin: 0; text-align: center; }
        .warning-box { background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; text-align: left; margin-bottom: 20px; }
        .warning-box p { margin: 0; font-size: 14px; color: #991b1b; }
        .warning-box strong { color: #7f1d1d; }
        .footer { background-color: #f8fafc; padding: 25px 20px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 0 0 10px; font-size: 13px; color: #64748b; line-height: 1.5; }
        .footer a { color: #0f6f9d; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="header">
                    <!-- [UPDATE]: Embed logo asli secara langsung ke dalam payload email menggunakan file fisik. 
                         Ini memastikan logo PASTI muncul tanpa diblokir oleh sistem anti-tracking Gmail. -->
                    <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="HikoKu Logo">
                    <h1>Verifikasi Keamanan</h1>
                </td>
            </tr>
            <tr>
                <td class="content">
                    <p>Halo,</p>
                    <p>Kami menerima permintaan untuk memverifikasi alamat email Anda di <strong>HikoKu</strong>. Gunakan kode rahasia di bawah ini untuk menyelesaikan proses verifikasi Anda:</p>
                    
                    <div class="otp-container">
                        <p class="otp-code">{{ $otpCode }}</p>
                    </div>
                    
                    <div class="warning-box">
                        <p><strong>⚠️ Peringatan Keamanan:</strong> Kode ini hanya berlaku selama <strong>5 menit</strong>. Jangan pernah membagikan kode ini kepada siapa pun. Admin HikoKu tidak akan pernah meminta kode ini.</p>
                    </div>
                    
                    <p style="font-size: 14px; color: #64748b; margin-top: 30px;">Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini. Akun Anda tetap aman dari akses tidak sah.</p>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <p>Pesan ini dikirim secara otomatis oleh sistem keamanan <strong>HikoKu</strong>.<br>Mohon untuk tidak membalas email ini karena alamat email ini tidak dipantau.</p>
                    <p>&copy; {{ date('Y') }} HikoKu. Seluruh hak cipta dilindungi undang-undang.</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
