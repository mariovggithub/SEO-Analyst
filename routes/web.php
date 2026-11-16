<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditPdfController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [AuditController::class, 'index'])->name('dashboard');

    Route::resource('audits', AuditController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::get('audits/{audit}/pdf', AuditPdfController::class)->name('audits.pdf');
});

require __DIR__.'/settings.php';
