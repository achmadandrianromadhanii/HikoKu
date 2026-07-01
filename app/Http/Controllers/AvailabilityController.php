<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::with(['category:id,name,slug', 'images'])
            ->active()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->search);
                $q->where('name', 'like', '%' . $term . '%');
            })
            ->get();

        return Inertia::render('Availability/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }
}
