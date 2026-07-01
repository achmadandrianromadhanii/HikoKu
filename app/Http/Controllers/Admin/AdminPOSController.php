<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Rental;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminPOSController extends Controller
{
    /**
     * Tampilkan halaman Antarmuka Kasir (POS)
     */
    public function index()
    {
        return Inertia::render('Admin/POS/Index', [
            'categories' => Category::all(),
        ]);
    }

    /**
     * Memuat daftar produk untuk Grid Katalog dengan Pagination dan Pencarian
     */
    public function products(Request $request)
    {
        $query = Product::with('images')->where('is_active', true);

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagination 10 item per halaman
        $products = $query->paginate(10);

        return response()->json($products);
    }

    /**
     * Memproses pesanan dari POS Kasir, bypass Midtrans
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_whatsapp' => 'required|string|max:255',
            'rental_start' => 'required|date',
            'rental_end' => 'required|date|after_or_equal:rental_start',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,qris,transfer',
            'amount_received' => 'nullable|numeric|min:0', // Untuk cash
        ]);

        return DB::transaction(function () use ($data) {
            // 1. Tangani Pelanggan (User)
            $phone = preg_replace('/[^0-9]/', '', $data['customer_whatsapp']);
            // Jika diawali 0, ganti ke 62
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $user = User::where('phone', $phone)->first();
            if (!$user) {
                // Buat user dummy jika belum ada
                $user = User::create([
                    'name' => $data['customer_name'],
                    'phone' => $phone,
                    'email' => $phone . '@offline.local', // Dummy email
                    'password' => bcrypt(Str::random(16)),
                    'is_active' => true,
                ]);
            }

            $start = Carbon::parse($data['rental_start']);
            $end = Carbon::parse($data['rental_end']);
            $days = max(1, $start->diffInDays($end) + 1);

            $subtotal = 0;
            $rentalItems = [];

            // 2. Cek stok dan hitung total
            foreach ($data['items'] as $itemData) {
                $product = Product::where('id', $itemData['product_id'])->lockForUpdate()->firstOrFail();

                if ((int)$product->stock_available < (int)$itemData['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak mencukupi. Tersisa: {$product->stock_available}",
                    ]);
                }

                // Potong stok seketika (menghindari overselling)
                $product->decrement('stock_available', (int)$itemData['quantity']);

                $itemSubtotal = (int)$itemData['quantity'] * (float)$product->price_per_day * $days;
                $subtotal += $itemSubtotal;

                $rentalItems[] = [
                    'item_type' => 'product',
                    'product_id' => $product->id,
                    'package_id' => null,
                    'product_name' => $product->name,
                    'quantity' => (int)$itemData['quantity'],
                    'price_per_day' => (float)$product->price_per_day,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $grandTotal = $subtotal;

            // 3. Buat Data Rental
            $rentalCode = 'RNT-' . date('YmdHis') . '-' . strtoupper(Str::random(4));

            $rental = Rental::create([
                'rental_code' => $rentalCode,
                'user_id' => $user->id,
                'status' => 'active', // Karena langsung diserahkan di toko
                'rental_start' => $start->toDateString(),
                'rental_end' => $end->toDateString(),
                'total_days' => $days,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'grand_total' => $grandTotal,
                // Kolom items_count tidak ada di tabel rentals, jadi dihapus
                'notes' => 'Sewa Offline (POS) - ' . strtoupper($data['payment_method']),
            ]);

            // Simpan Rental Items
            $rental->items()->createMany($rentalItems);

            // 4. Buat Data Payment (LUNAS, Bypass Midtrans)
            $paymentCode = 'PAY-' . date('YmdHis') . '-' . strtoupper(Str::random(4));
            
            Payment::create([
                'rental_id' => $rental->id,
                'payment_code' => $paymentCode,
                'method' => $data['payment_method'],
                'amount' => $grandTotal,
                'status' => 'paid',
                'paid_at' => now(),
                'confirmed_by' => auth()->guard('admin')->id() ?? auth()->id(),
                'notes' => 'Pembayaran otomatis kasir offline',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan!',
                'rental_code' => $rental->rental_code,
            ]);
        });
    }
}
