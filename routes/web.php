<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\LorController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckItAdmin;
use App\Http\Middleware\CheckMenuPermission;

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes (Require Login)
Route::middleware(['auth'])->group(function () {

    // Main Dashboard & Analytics
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/details', [DashboardController::class, 'details'])->name('details');
    Route::get('/export', [DashboardController::class, 'export'])->name('export');
    Route::get('/print', [DashboardController::class, 'print'])->name('print');

    // Total Stock Pages
    Route::get('/total-stock', [DashboardController::class, 'totalStock'])->middleware(CheckMenuPermission::class . ':total-stock')->name('total.stock');
    Route::post('/total-stock/filter', [DashboardController::class, 'filterTotalStock'])->middleware(CheckMenuPermission::class . ':total-stock')->name('total.stock.filter');
    Route::post('/total-stock/export', [DashboardController::class, 'exportTotalStock'])->middleware(CheckMenuPermission::class . ':total-stock')->name('total.stock.export');

    // Rental Pairs
    Route::get('/rental-pairs', [DashboardController::class, 'rentalPairs'])->middleware(CheckMenuPermission::class . ':rental-pairs')->name('rental.pairs');

    // Active Rentals & Reports
    Route::get('/summary', [DashboardController::class, 'summary'])->name('summary');
    Route::get('/help', function () { return view('help'); })->name('help');
    Route::post('/generate', [DashboardController::class, 'upload'])->name('summary.generate');
    Route::get('/active-rentals/by-customer', [DashboardController::class, 'activeRentalsByCustomer'])->name('active-rentals.by-customer');
    Route::get('/active-rentals/by-customer/export', [DashboardController::class, 'exportActiveRentalsByCustomer'])->name('active-rentals.by-customer.export');

    // APIs
    Route::get('/api/suggestions', [DashboardController::class, 'suggestions'])->name('api.suggestions');
    Route::get('/api/repair-history/{lotNumber}', [DashboardController::class, 'repairHistory'])->name('api.repair.history');
    Route::get('/api/traceability/{lotNumber}', [DashboardController::class, 'traceabilityReport'])->name('api.traceability');
    Route::get('/api/location-history', [DashboardController::class, 'apiLocationHistory'])->name('api.location.history');
    Route::get('/api/settings/targets', [SettingsController::class, 'getTargets'])->name('api.settings.targets');

    // LoR Routes (Protected by Menu Permission & Secondary Password)
    Route::middleware([CheckMenuPermission::class . ':lor'])->group(function () {
        Route::get('/lor', [LorController::class, 'index'])->name('lor.index');
        Route::get('/lor/export', [LorController::class, 'export'])->name('lor.export');
        Route::get('/lor/full-history', [LorController::class, 'getFullHistory'])->name('lor.full-history');
        Route::post('/lor/auth', [LorController::class, 'authenticate'])->middleware('throttle:10,1')->name('lor.auth');
        Route::post('/settings/lor', [LorController::class, 'updatePassword'])->name('lor.settings.update');
        Route::get('/lor/rental-details', [LorController::class, 'getRentalDetails'])->name('lor.rental-details');
    });

    // CRM Routes (Protected by Menu Permission & Secondary Password)
    Route::middleware([CheckMenuPermission::class . ':crm'])->group(function () {
        Route::get('/crm', [CrmController::class, 'index'])->name('crm.index');
        Route::post('/crm/auth', [CrmController::class, 'authenticate'])->middleware('throttle:10,1')->name('crm.auth');
        Route::get('/settings/crm', [CrmController::class, 'settings'])->name('crm.settings');
        Route::post('/settings/crm', [CrmController::class, 'updatePassword'])->name('crm.settings.update');
    });

    // IT Admin Only Routes (Utilities & User Management)
    Route::middleware([CheckItAdmin::class])->group(function () {
        // User & Branch Management
        Route::get('/settings/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/settings/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/settings/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/settings/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/hq/branch/switch', [UserController::class, 'setHqBranch'])->name('hq.branch.switch');

        // Import & Odoo Utilities
        Route::get('/import', [ImportController::class, 'index'])->name('import');
        Route::post('/import/excel', [ImportController::class, 'uploadExcel'])->name('import.excel');
        Route::post('/import/odoo/config', [ImportController::class, 'saveOdooConfig'])->name('import.odoo.config');
        Route::post('/import/odoo/test', [ImportController::class, 'testOdooConnection'])->name('import.odoo.test');
        Route::post('/import/odoo/sync', [ImportController::class, 'syncOdoo'])->name('import.odoo.sync');
        Route::get('/import/odoo/schedule', [ImportController::class, 'getSchedule'])->name('import.odoo.schedule.get');
        Route::post('/import/odoo/schedule', [ImportController::class, 'saveSchedule'])->name('import.odoo.schedule.save');
        Route::get('/import/history', [ImportController::class, 'history'])->name('import.history');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/targets', [SettingsController::class, 'updateTargets'])->name('settings.targets');
        Route::post('/settings/odoo', [SettingsController::class, 'updateOdoo'])->name('settings.odoo');
    });
});
