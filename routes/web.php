<?php

use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\QuotationPdfController;
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

    Route::get('/quotation/pdf/{id}', [QuotationPdfController::class, 'show'])
        ->name('quotation.pdf');
});
