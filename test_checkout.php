<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::first();
    \App\Models\CartItem::firstOrCreate([
        'user_id' => $user->id,
        'item_type' => 'product',
        'product_id' => \App\Models\Product::first()->id,
    ], ['quantity' => 1, 'expires_at' => now()->addMinutes(15)]);

    $request = \Illuminate\Http\Request::create('/checkout', 'POST', [
        'rental_start' => '2026-06-26',
        'rental_end' => '2026-06-30',
        'notes' => ''
    ]);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    $controller = app(\App\Http\Controllers\RentalController::class);
    $response = $controller->store($request);

    echo "SUCCESS\n";
    print_r($response);
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
