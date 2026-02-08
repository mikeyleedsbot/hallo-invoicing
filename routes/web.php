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
    
    // Invoices
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf', [App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview', [App\Http\Controllers\InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/print', [App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
});

require __DIR__.'/auth.php';
