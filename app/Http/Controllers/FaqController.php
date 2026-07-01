<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Faq/Index', [
            'faqs' => Faq::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
