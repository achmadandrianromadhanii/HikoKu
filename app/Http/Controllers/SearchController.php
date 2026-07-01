<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    public function autocomplete(Request $request)
    {
        $request->validate([
            'term' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'results' => $this->searchService->autocomplete((string) $request->term),
        ]);
    }
}
