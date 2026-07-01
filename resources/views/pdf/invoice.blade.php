<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice & E-Ticket - {{ $rental->rental_code }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .header { width: 100%; display: table; margin-bottom: 40px; }
        .header-col { display: table-cell; vertical-align: top; }
        .header-left { width: 50%; }
        .header-right { width: 50%; text-align: right; }
        .title { font-size: 24px; font-weight: bold; color: #0ea5e9; }
        .info-table { width: 100%; margin-bottom: 30px; }
        .info-table td { padding: 5px 0; }
        .qr-container { text-align: center; margin: 20px 0; }
        .qr-code { width: 150px; height: 150px; border: 2px solid #000; padding: 5px; border-radius: 10px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th, .items-table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .items-table th { background-color: #f8f9fa; font-weight: bold; }
        .items-table td.right { text-align: right; }
        .total-row { font-weight: bold; font-size: 16px; color: #0ea5e9; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="header-col header-left">
                <div class="title">HIKO APP</div>
                <p>E-Ticket & Invoice Penyewaan</p>
            </div>
            <div class="header-col header-right">
                <h2>{{ $rental->rental_code }}</h2>
                <p>Status: <span style="color: green;">LUNAS ({{ strtoupper($rental->status) }})</span></p>
            </div>
        </div>

        <table class="info-table">
            <tr>
                <td width="150"><strong>Nama Penyewa</strong></td>
                <td>: {{ $rental->user->name }}</td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td>: {{ $rental->user->email }}</td>
            </tr>
            <tr>
                <td><strong>Tgl Mulai</strong></td>
                <td>: {{ \Carbon\Carbon::parse($rental->rental_start)->format('d M Y') }}</td>
            </tr>
            <tr>
                <td><strong>Tgl Selesai</strong></td>
                <td>: {{ \Carbon\Carbon::parse($rental->rental_end)->format('d M Y') }} ({{ $rental->total_days }} Hari)</td>
            </tr>
        </table>

        <!-- QR Code Raksasa untuk di Scan -->
        <div class="qr-container">
            <p><strong>SCAN BARCODE INI SAAT PENGAMBILAN & PENGEMBALIAN</strong></p>
            <img src="data:image/png;base64,{{ $qrCode }}" class="qr-code" alt="QR Code">
        </div>

        <h3>Rincian Pesanan</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th class="right">Harga / Hari</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rental->items as $item)
                <tr>
                    <td>{{ $item->product_name }} <br><small>({{ ucfirst($item->item_type) }})</small></td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td class="right">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="right"><strong>Total Harga Sewa</strong></td>
                    <td class="right">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" class="right">GRAND TOTAL LUNAS</td>
                    <td class="right">Rp {{ number_format($rental->grand_total, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Harap bawa KTP Asli saat pengambilan barang sebagai jaminan.</p>
            <p>Terima kasih telah menggunakan layanan Hiko App.</p>
        </div>
    </div>
</body>
</html>
