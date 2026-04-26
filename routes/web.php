<?php

use App\Http\Controllers\QuickSaleReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/quick-sale/receipt/{id}', [QuickSaleReceiptController::class, 'show'])
        ->name('quick-sale.receipt');
});
