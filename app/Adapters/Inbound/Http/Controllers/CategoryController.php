<?php

declare(strict_types=1);

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\Services\ListCategoriesService;
use Illuminate\Http\JsonResponse;

final class CategoryController
{
    public function __construct(private readonly ListCategoriesService $listCategories)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['items' => $this->listCategories->list()]);
    }
}
