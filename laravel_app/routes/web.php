<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\SummaryController;

use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/details', [DashboardController::class, 'details'])->name('details');
Route::get('/export', [DashboardController::class, 'export'])->name('export');
Route::get('/print', [DashboardController::class, 'print'])->name('print');
Route::get('/rental-pairs', [DashboardController::class, 'rentalPairs'])->name('rental.pairs');
Route::post('/generate', [SummaryController::class, 'generate'])->name('summary.generate');

