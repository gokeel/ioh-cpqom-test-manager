<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Unauthenticated — called by Playwright/automation runner
Route::get('/product-test-runs/{productTestRun}', [App\Http\Controllers\ProductTestRunController::class, 'show'])->name('product-test-runs.show');
Route::put('/product-test-runs/{productTestRun}', [App\Http\Controllers\ProductTestRunController::class, 'update'])->name('product-test-runs.update');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin — User management
    Route::resource('admin/users', App\Http\Controllers\Admin\UserController::class)
        ->except(['create', 'edit', 'show'])
        ->names('admin.users');

    // Admin Tester & Persona Routes
    Route::resource('testers', App\Http\Controllers\Admin\TesterController::class);
    Route::resource('sf-users', App\Http\Controllers\Admin\SalesforceUserController::class);
    Route::resource('sf-environments', App\Http\Controllers\SfEnvironmentController::class)
        ->except(['create', 'edit', 'show']);
    Route::get('/auth/salesforce/redirect/{sf_user_id}', [App\Http\Controllers\SalesforceOAuthController::class, 'redirect'])->name('salesforce.redirect');
    Route::get('/auth/salesforce/callback', [App\Http\Controllers\SalesforceOAuthController::class, 'callback'])->name('salesforce.callback');
    Route::resource('object-sync', App\Http\Controllers\ObjectSyncController::class);

    // CPQ Simulator
    Route::get('/cpq-simulator', [App\Http\Controllers\CpqSimulatorController::class, 'index'])->name('cpq-simulator.index');
    Route::post('/cpq-simulator/proxy', [App\Http\Controllers\CpqSimulatorController::class, 'proxy'])->name('cpq-simulator.proxy');
    Route::get('/cpq-simulator/root-products', [App\Http\Controllers\CpqSimulatorController::class, 'rootProducts'])->name('cpq-simulator.root-products');

    // Test Suite
    Route::get('/test-suite', [App\Http\Controllers\TestSuiteController::class, 'index'])->name('test-suite.index');
    Route::get('/test-suite/import', [App\Http\Controllers\TestSuiteImportController::class, 'index'])->name('test-suite.import.index');
    Route::post('/test-suite/import/preview', [App\Http\Controllers\TestSuiteImportController::class, 'preview'])->name('test-suite.import.preview');
    Route::post('/test-suite/import/confirm', [App\Http\Controllers\TestSuiteImportController::class, 'confirm'])->name('test-suite.import.confirm');
    Route::get('/test-suite/{testModule}', [App\Http\Controllers\TestSuiteController::class, 'show'])->name('test-suite.show');
    Route::post('/test-suite/{testModule}/counter/increment', [App\Http\Controllers\TestSuiteController::class, 'incrementCounter'])->name('test-suite.counter.increment');
    Route::post('/test-suite/{testModule}/counter/reset', [App\Http\Controllers\TestSuiteController::class, 'resetCounter'])->name('test-suite.counter.reset');
    Route::put('/test-suite/{testModule}/spec', [App\Http\Controllers\TestSuiteController::class, 'updateSpec'])->name('test-suite.spec.update');
    Route::post('/test-suite/{testModule}/run', [App\Http\Controllers\TestSuiteController::class, 'runSpec'])->name('test-suite.run');
    Route::get('/test-suite/{testModule}/api-test', [App\Http\Controllers\QuoteApiTestController::class, 'show'])->name('test-suite.api-test.show');
    Route::post('/test-suite/{testModule}/api-test/run', [App\Http\Controllers\QuoteApiTestController::class, 'run'])->name('test-suite.api-test.run');

    // Spec Files management
    Route::get('/test-specs', [App\Http\Controllers\TestSpecController::class, 'index'])->name('test-specs.index');
    Route::post('/test-specs', [App\Http\Controllers\TestSpecController::class, 'store'])->name('test-specs.store');
    Route::put('/test-specs/{testSpec}', [App\Http\Controllers\TestSpecController::class, 'update'])->name('test-specs.update');
    Route::delete('/test-specs/{testSpec}', [App\Http\Controllers\TestSpecController::class, 'destroy'])->name('test-specs.destroy');
    Route::post('/test-suite/{testModule}/parameters', [App\Http\Controllers\TestSuiteController::class, 'storeParameter'])->name('test-suite.parameters.store');
    Route::put('/test-suite/parameters/{testParameter}', [App\Http\Controllers\TestSuiteController::class, 'updateParameter'])->name('test-suite.parameters.update');
    Route::delete('/test-suite/parameters/{testParameter}', [App\Http\Controllers\TestSuiteController::class, 'destroyParameter'])->name('test-suite.parameters.destroy');
    Route::post('/test-suite/runtime-state', [App\Http\Controllers\TestSuiteController::class, 'storeRuntimeState'])->name('test-suite.runtime.store');
    Route::put('/test-suite/runtime-state/{runtimeState}', [App\Http\Controllers\TestSuiteController::class, 'updateRuntimeState'])->name('test-suite.runtime.update');
    Route::delete('/test-suite/runtime-state/{runtimeState}', [App\Http\Controllers\TestSuiteController::class, 'destroyRuntimeState'])->name('test-suite.runtime.destroy');
    
    // Product Test Suites
    Route::get('/product-test-suites/products', [App\Http\Controllers\ProductTestSuiteController::class, 'products'])->name('product-test-suites.products');
    Route::post('/product-test-suites/{productTestSuite}/run', [App\Http\Controllers\ProductTestSuiteController::class, 'run'])->name('product-test-suites.run');
    Route::post('/product-test-suites/{productTestSuite}/run/{testModule}', [App\Http\Controllers\ProductTestSuiteController::class, 'runModule'])->name('product-test-suites.run-module');
    Route::resource('product-test-suites', App\Http\Controllers\ProductTestSuiteController::class);
    Route::post('/product-test-runs/{productTestRun}/findings', [App\Http\Controllers\ProductTestRunController::class, 'storeFinding'])->name('product-test-runs.findings.store');
    Route::get('/product-test-suites/{productTestSuite}/modules/{testModule}/runs', [App\Http\Controllers\ProductTestRunController::class, 'history'])->name('product-test-runs.history');

    // Modules & Test Cases
    Route::resource('modules', App\Http\Controllers\ModuleController::class);
    Route::put('/modules/{module}/link-test-suite', [App\Http\Controllers\ModuleController::class, 'linkTestSuite'])->name('modules.link-test-suite');
    Route::resource('modules.test-cases', App\Http\Controllers\TestCaseController::class);
    Route::resource('test-runs', App\Http\Controllers\TestRunController::class)->only(['index', 'show', 'store']);
});

require __DIR__.'/auth.php';
