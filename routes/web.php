<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailSettingController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'mfa'])
    ->name('dashboard');

// MFA routes (auth vereist, maar nog geen mfa check)
Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup',    [MfaController::class, 'setup'])->name('mfa.setup');
    Route::post('/mfa/confirm', [MfaController::class, 'confirm'])->name('mfa.confirm');
    Route::get('/mfa/verify',   [MfaController::class, 'verify'])->name('mfa.verify');
    Route::post('/mfa/check',   [MfaController::class, 'check'])->name('mfa.check');
    Route::post('/mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');
});

Route::middleware(['auth', 'mfa'])->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Customers
    Route::resource('customers', App\Http\Controllers\CustomerController::class)->except(['show', 'create', 'edit']);

    // Products
    Route::resource('products', App\Http\Controllers\ProductController::class)->except(['show', 'create', 'edit']);

    // Company Settings
    Route::get('/company', [App\Http\Controllers\CompanySettingController::class, 'edit'])->name('company.edit');
    Route::put('/company', [App\Http\Controllers\CompanySettingController::class, 'update'])->name('company.update');

    // App Settings
    Route::get('/settings',  [AppSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings',  [AppSettingController::class, 'update'])->name('settings.update');

    // Gebruikersbeheer (admin only)
    Route::get('/gebruikers',                              [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/gebruikers',                             [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/gebruikers/{user}',                       [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/gebruikers/{user}',                    [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::post('/gebruikers/{user}/reset-mfa',            [UserManagementController::class, 'resetMfa'])->name('users.reset-mfa');
    Route::post('/gebruikers/{user}/resend-invite',        [UserManagementController::class, 'resendInvite'])->name('users.resend-invite');

    // E-mailinstellingen (admin only)
    Route::get('/email-instellingen',                      [EmailSettingController::class, 'edit'])->name('email-settings.edit');
    Route::put('/email-instellingen',                      [EmailSettingController::class, 'update'])->name('email-settings.update');
    Route::post('/email-instellingen/test',                [EmailSettingController::class, 'test'])->name('email-settings.test');

    // Quotes
    Route::resource('quotes', App\Http\Controllers\QuoteController::class);
    Route::get('/quotes/{quote}/pdf',         [App\Http\Controllers\QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::get('/quotes/{quote}/preview',     [App\Http\Controllers\QuoteController::class, 'preview'])->name('quotes.preview');
    Route::get('/quotes/{quote}/print',       [App\Http\Controllers\QuoteController::class, 'print'])->name('quotes.print');
    Route::post('/quotes/{quote}/convert',    [App\Http\Controllers\QuoteController::class, 'convertToInvoice'])->name('quotes.convert');
    Route::post('/quotes/{quote}/mark-sent',  [App\Http\Controllers\QuoteController::class, 'markSent'])->name('quotes.mark-sent');

    // Invoices
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf',         [App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview',     [App\Http\Controllers\InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/print',       [App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('/invoices/{invoice}/mark-sent',  [App\Http\Controllers\InvoiceController::class, 'markSent'])->name('invoices.mark-sent');
    Route::post('/invoices/{invoice}/mark-paid',  [App\Http\Controllers\InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{invoice}/duplicate',  [App\Http\Controllers\InvoiceController::class, 'duplicate'])->name('invoices.duplicate');

    // Invoice Templates
    Route::resource('templates', App\Http\Controllers\TemplateController::class);
    Route::post('/templates/{template}/set-default',  [App\Http\Controllers\TemplateController::class, 'setDefault'])->name('templates.set-default');
    Route::get('/templates/{template}/editor',        [App\Http\Controllers\TemplateController::class, 'editor'])->name('templates.editor');
    Route::post('/templates/{template}/positions',    [App\Http\Controllers\TemplateController::class, 'savePositions'])->name('templates.save-positions');
    Route::get('/templates/{template}/test-pdf',      [App\Http\Controllers\TemplateController::class, 'testPdf'])->name('templates.test-pdf');
    Route::post('/templates/{template}/upload-logo',  [App\Http\Controllers\TemplateController::class, 'uploadLogo'])->name('templates.upload-logo');
});

// Uitnodiging accepteren (publiek, geen auth)
Route::get('/uitnodiging/{token}',   [InviteController::class, 'accept'])->name('invite.accept');
Route::post('/uitnodiging/{token}',  [InviteController::class, 'activate'])->name('invite.activate');

require __DIR__.'/auth.php';
