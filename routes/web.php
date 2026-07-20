<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailSettingController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VatRateController;
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
    Route::post('/mfa/confirm', [MfaController::class, 'confirm'])->middleware('throttle:6,1')->name('mfa.confirm');
    Route::get('/mfa/verify',   [MfaController::class, 'verify'])->name('mfa.verify');
    Route::post('/mfa/check',   [MfaController::class, 'check'])->middleware('throttle:6,1')->name('mfa.check');
    Route::post('/mfa/disable', [MfaController::class, 'disable'])->middleware('throttle:6,1')->name('mfa.disable');
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

    // BTW Tarieven
    Route::get('/btw-tarieven',                    [VatRateController::class, 'index'])->name('vat-rates.index');
    Route::post('/btw-tarieven',                   [VatRateController::class, 'store'])->name('vat-rates.store');
    Route::put('/btw-tarieven/{vatRate}',           [VatRateController::class, 'update'])->name('vat-rates.update');
    Route::delete('/btw-tarieven/{vatRate}',        [VatRateController::class, 'destroy'])->name('vat-rates.destroy');
    Route::post('/btw-tarieven/{vatRate}/default',  [VatRateController::class, 'setDefault'])->name('vat-rates.set-default');

    // Gebruikersbeheer (admin only — 'admin' middleware + inline checks in controller)
    Route::middleware('admin')->group(function () {
        Route::get('/gebruikers',                              [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/gebruikers',                             [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/gebruikers/{user}',                       [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/gebruikers/{user}',                    [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::post('/gebruikers/{user}/reset-mfa',            [UserManagementController::class, 'resetMfa'])->name('users.reset-mfa');
        Route::post('/gebruikers/{user}/resend-invite',        [UserManagementController::class, 'resendInvite'])->name('users.resend-invite');
        Route::post('/gebruikers/{user}/approve',              [UserManagementController::class, 'approve'])->name('users.approve');
        Route::post('/gebruikers/{user}/reject',               [UserManagementController::class, 'reject'])->name('users.reject');
        Route::post('/gebruikers/{user}/impersonate',          [App\Http\Controllers\ImpersonationController::class, 'start'])->name('users.impersonate');

        // E-mailinstellingen (admin only)
        Route::get('/email-instellingen',                      [EmailSettingController::class, 'edit'])->name('email-settings.edit');
        Route::post('/email-instellingen/test',                [EmailSettingController::class, 'test'])->name('email-settings.test');
    });

    // Impersonatie stoppen (géén admin-middleware: tijdens het meekijken
    // ben je immers ingelogd als de niet-admin gebruiker)
    Route::post('/impersonatie/stop', [App\Http\Controllers\ImpersonationController::class, 'stop'])->name('impersonation.stop');

    // Persoonlijke mailverbindingen (OAuth Google/Microsoft)
    Route::get('/mailverbindingen',                                        [App\Http\Controllers\MailConnectionController::class, 'index'])->name('mail-connections.index');
    Route::get('/mailverbindingen/oauth/{provider}/redirect',              [App\Http\Controllers\MailConnectionController::class, 'redirect'])->name('mail-connections.redirect');
    Route::get('/mailverbindingen/oauth/{provider}/callback',              [App\Http\Controllers\MailConnectionController::class, 'callback'])->name('mail-connections.callback');
    Route::post('/mailverbindingen/{account}/default',                     [App\Http\Controllers\MailConnectionController::class, 'setDefault'])->name('mail-connections.set-default');
    Route::delete('/mailverbindingen/{account}',                           [App\Http\Controllers\MailConnectionController::class, 'destroy'])->name('mail-connections.destroy');
    // Per-user OAuth-credentials (client_id / client_secret) — staan op users tabel
    Route::post('/mailverbindingen/credentials/{provider}',                [App\Http\Controllers\MailConnectionController::class, 'saveCredentials'])->name('mail-connections.credentials.save');
    Route::delete('/mailverbindingen/credentials/{provider}',              [App\Http\Controllers\MailConnectionController::class, 'deleteCredentials'])->name('mail-connections.credentials.delete');

    // Quotes
    Route::resource('quotes', App\Http\Controllers\QuoteController::class);
    Route::get('/quotes/{quote}/pdf',         [App\Http\Controllers\QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::get('/quotes/{quote}/preview',     [App\Http\Controllers\QuoteController::class, 'preview'])->name('quotes.preview');
    Route::get('/quotes/{quote}/print',       [App\Http\Controllers\QuoteController::class, 'print'])->name('quotes.print');
    Route::post('/quotes/{quote}/convert',    [App\Http\Controllers\QuoteController::class, 'convertToInvoice'])->name('quotes.convert');
    Route::post('/quotes/{quote}/mark-sent',  [App\Http\Controllers\QuoteController::class, 'markSent'])->name('quotes.mark-sent');
    Route::post('/quotes/{quote}/send-email', [App\Http\Controllers\QuoteController::class, 'sendEmail'])->name('quotes.send-email');

    // Invoices
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf',         [App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview',     [App\Http\Controllers\InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/print',       [App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('/invoices/{invoice}/mark-sent',  [App\Http\Controllers\InvoiceController::class, 'markSent'])->name('invoices.mark-sent');
    Route::post('/invoices/{invoice}/mark-paid',  [App\Http\Controllers\InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{invoice}/duplicate',  [App\Http\Controllers\InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::post('/invoices/{invoice}/send-email', [App\Http\Controllers\InvoiceController::class, 'sendEmail'])->name('invoices.send-email');

    // Help / Instructies
    Route::get('/help',          [HelpController::class, 'index'])->name('help.index');
    Route::get('/help/{topic}',  [HelpController::class, 'show'])->name('help.show');

    // Invoice Templates
    Route::resource('templates', App\Http\Controllers\TemplateController::class);
    Route::post('/templates/{template}/set-default',  [App\Http\Controllers\TemplateController::class, 'setDefault'])->name('templates.set-default');
    Route::get('/templates/{template}/editor',        [App\Http\Controllers\TemplateController::class, 'editor'])->name('templates.editor');
    Route::post('/templates/{template}/positions',    [App\Http\Controllers\TemplateController::class, 'savePositions'])->name('templates.save-positions');
    Route::get('/templates/{template}/test-pdf',      [App\Http\Controllers\TemplateController::class, 'testPdf'])->name('templates.test-pdf');
    Route::post('/templates/{template}/upload-logo',  [App\Http\Controllers\TemplateController::class, 'uploadLogo'])->name('templates.upload-logo');
    Route::get('/template-bestanden/{template}/{type}', [App\Http\Controllers\TemplateController::class, 'serveFile'])->name('templates.serve-file')->where('type', 'logo|background');
});

// Uitnodiging accepteren (publiek, geen auth — throttled tegen token-brute-force)
Route::get('/uitnodiging/{token}',   [InviteController::class, 'accept'])->middleware('throttle:20,1')->name('invite.accept');
Route::post('/uitnodiging/{token}',  [InviteController::class, 'activate'])->middleware('throttle:10,1')->name('invite.activate');

require __DIR__.'/auth.php';
