<?php

namespace App\Services;

use App\Imports\ProductImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportService
{
    public function import(string $path): void
    {
        Excel::import(new ProductImport, $path);
        CacheService::bustProducts();
        CacheService::bustCategories();
    }
}
