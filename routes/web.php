<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Customers
    Route::resource('customers', App\Http\Controllers\CustomerController::class)->except(['show', 'create', 'edit']);
    
    // Products
    Route::resource('products', App\Http\Controllers\ProductController::class)->except(['show', 'create', 'edit']);
    
    // Company Settings
    Route::get('/company', [App\Http\Controllers\CompanySettingController::class, 'edit'])->name('company.edit');
    Route::put('/company', [App\Http\Controllers\CompanySettingController::class, 'update'])->name('company.update');
    
    // App Settings
    Route::get('/settings', [App\Http\Controllers\AppSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [App\Http\Controllers\AppSettingController::class, 'update'])->name('settings.update');
    
    // Quotes (Offertes)
    Route::resource('quotes', App\Http\Controllers\QuoteController::class);
    Route::get('/quotes/{quote}/pdf', [App\Http\Controllers\QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::get('/quotes/{quote}/preview', [App\Http\Controllers\QuoteController::class, 'preview'])->name('quotes.preview');
    Route::get('/quotes/{quote}/print', [App\Http\Controllers\QuoteController::class, 'print'])->name('quotes.print');
    Route::post('/quotes/{quote}/convert', [App\Http\Controllers\QuoteController::class, 'convertToInvoice'])->name('quotes.convert');
    
    // Invoices
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf', [App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview', [App\Http\Controllers\InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/print', [App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
});

require __DIR__.'/auth.php';
