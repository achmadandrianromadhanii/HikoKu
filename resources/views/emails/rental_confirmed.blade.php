<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sewa Anda Telah Dikonfirmasi</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #0ea5e9; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Hiko App - E-Ticket</h2>
        </div>
        
        <p>Halo, <strong>{{ $rental->user->name }}</strong>,</p>
        
        <p>Sewa barang Anda dengan kode <strong>{{ $rental->rental_code }}</strong> sudah kami terima dan telah <strong>DIKONFIRMASI</strong>.</p>
        
        <p>Silakan datang ke toko kami pada tanggal <strong>{{ \Carbon\Carbon::parse($rental->rental_start)->translatedFormat('d F Y') }}</strong> untuk mengambil barang yang Anda sewa.</p>
        
        <p><strong>Penting:</strong></p>
        <ul>
            <li>Bawa kartu identitas asli (KTP/SIM/dll) sebagai jaminan wajib.</li>
            <li>Tunjukkan E-Ticket (PDF terlampir) atau dari website untuk di-scan oleh Admin.</li>
        </ul>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/') }}" class="btn">Buka Website</a>
        </div>

        <p>Terima kasih telah mempercayakan kebutuhan Anda pada Hiko App!</p>

        <div class="footer">
            <p>Hiko App &copy; {{ date('Y') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
