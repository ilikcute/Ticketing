<?php

use App\Http\Controllers\BibCheckController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LoketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- Bawaan Breeze — WAJIB tetap ada ---
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:access-dashboard'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --- Publik: kiosk BIB Check (tanpa login, read-only) ---
Route::get('/bib-check', [BibCheckController::class, 'index'])->name('bib-check.index');
Route::get('/bib-check/{code}', [BibCheckController::class, 'check'])->name('bib-check.check');

// --- Modul aplikasi kita, wajib login ---
Route::middleware(['auth'])->group(function () {

    // Modul Loket — role: admin, loket
    Route::middleware('role:admin,loket')->prefix('loket')->name('loket.')->group(function () {
        Route::get('/', [LoketController::class, 'index'])->name('index');
        Route::get('/lookup/{pinCode?}', [LoketController::class, 'lookup'])->name('lookup');
        Route::post('/assign', [LoketController::class, 'assign'])->name('assign');
        Route::post('/reset-claim', [LoketController::class, 'resetClaim'])->name('reset-claim');
    });

    // Modul Import & User Management — role: admin
    Route::middleware('role:admin')->group(function () {
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [ImportController::class, 'index'])->name('index');
            Route::get('/template', [ImportController::class, 'downloadTemplate'])->name('template');
            Route::post('/', [ImportController::class, 'store'])->name('store');
            Route::get('/{batch}/result', [ImportController::class, 'result'])->name('result');
            Route::get('/{batch}/errors/download', [ImportController::class, 'downloadErrors'])->name('errors.download');
            Route::get('/{batch}/duplicates/download', [ImportController::class, 'downloadDuplicates'])->name('duplicates.download');
        });

        // Modul Manajemen User & Role Matrix
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::post('/role-permissions', [UserController::class, 'updateRolePermissions'])->name('role-permissions');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });

    // Modul Undian — role: admin, undian
    // Route::middleware('role:admin,undian')->prefix('lottery')->name('lottery.')->group(function () {
    //     Route::get('/', [LotteryController::class, 'index'])->name('index');
    //     Route::post('/{pool}/draw', [LotteryController::class, 'draw'])->name('draw');
    // });
});

require __DIR__ . '/auth.php';
