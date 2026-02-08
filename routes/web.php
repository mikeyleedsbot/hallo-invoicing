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
    
    // Quotes (Offertes) - Coming Soon
    Route::get('/quotes', [App\Http\Controllers\QuoteController::class, 'index'])->name('quotes.index');
    
    // Invoices
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf', [App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview', [App\Http\Controllers\InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/print', [App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
});

require __DIR__.'/auth.php';
