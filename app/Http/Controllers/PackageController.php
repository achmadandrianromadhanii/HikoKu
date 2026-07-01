<?php

namespace App\Http\Controllers;

use App\Models\ProductPackage;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(): Response
    {
        $packages = ProductPackage::with(['items.product:id,name,slug'])
            ->active()
            ->latest()
            ->paginate(12);

        return Inertia::render('Packages/Index', [
            'packages' => $packages,
        ]);
    }

    public function show(string $slug): Response
    {
        $package = ProductPackage::with([
            'items.product:id,name,slug,price_per_day,stock_available',
        ])->where('slug', $slug)->active()->firstOrFail();

        return Inertia::render('Packages/Show', [
            'package' => $package,
        ]);
    }
}
