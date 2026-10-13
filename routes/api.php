<?php

declare(strict_types=1);

use App\Adapters\Inbound\Http\Controllers\AuthController;
use App\Adapters\Inbound\Http\Controllers\CategoryController;
use App\Adapters\Inbound\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth.token'])->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index']);
});
