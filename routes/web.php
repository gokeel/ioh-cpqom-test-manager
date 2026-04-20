<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [App\Http\Controllers\ModuleController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Tester & Persona Routes
    Route::resource('testers', App\Http\Controllers\Admin\TesterController::class);
    Route::resource('sf-users', App\Http\Controllers\Admin\SalesforceUserController::class);
    Route::get('/auth/salesforce/redirect/{sf_user_id}', [App\Http\Controllers\SalesforceOAuthController::class, 'redirect'])->name('salesforce.redirect');
    Route::get('/auth/salesforce/callback', [App\Http\Controllers\SalesforceOAuthController::class, 'callback'])->name('salesforce.callback');
    Route::resource('object-sync', App\Http\Controllers\ObjectSyncController::class);

    // CPQ Simulator
    Route::get('/cpq-simulator', [App\Http\Controllers\CpqSimulatorController::class, 'index'])->name('cpq-simulator.index');
    Route::post('/cpq-simulator/proxy', [App\Http\Controllers\CpqSimulatorController::class, 'proxy'])->name('cpq-simulator.proxy');
    
    // Modules & Test Cases
    Route::resource('modules', App\Http\Controllers\ModuleController::class);
    Route::resource('modules.test-cases', App\Http\Controllers\TestCaseController::class);
    Route::resource('test-runs', App\Http\Controllers\TestRunController::class)->only(['index', 'show', 'store']);
});

require __DIR__.'/auth.php';
