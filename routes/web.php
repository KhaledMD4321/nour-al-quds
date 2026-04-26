<?php

use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\QuickSaleReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/quick-sale/receipt/{id}', [QuickSaleReceiptController::class, 'show'])
        ->name('quick-sale.receipt');

    Route::get('/invoice/pdf/{id}', [InvoicePdfController::class, 'show'])
        ->name('invoice.pdf');
});
