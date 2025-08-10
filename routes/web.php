<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});


Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth:sanctum');

Route::get('/profile', [HomeController::class, 'index'])->name('profile')->middleware('auth:sanctum');

Route::get('/clients', [ClientController::class, 'index'])->name('clients')->middleware('auth:sanctum');

Route::get('/billing', [BillingController::class, 'index'])->name('billing')->middleware('web','auth:sanctum');

Auth::routes();

Route::get('/virtual-reality', [HomeController::class, 'index'])->name('virtual-reality')->middleware('auth:sanctum');

Route::get('/rtl', [HomeController::class, 'index'])->name('rtl')->middleware('auth:sanctum');

Route::get('/profile-static', [HomeController::class, 'index'])->name('profile-static')->middleware('auth:sanctum');

Route::get('/sign-in-static', [HomeController::class, 'index'])->name('sign-in-static')->middleware('auth:sanctum');

Route::get('/sign-up-static', [HomeController::class, 'index'])->name('sign-up-static')->middleware('auth:sanctum');

Route::get('/migrate', function () {
    // Check that the environment is not production before running the migration
    if (app()->environment('production')) {
        abort(403, 'Unauthorized action.');
    }

    // Ensure the user has an appropriate role or permission (e.g., 'admin')
    // if (!auth()->user()->hasRole('superAdmin')) {
    //     abort(403, 'Unauthorized action.');
    // }

    // Run the migration with the '--force' flag
    Artisan::call('migrate', ['--force' => true]);
    return response()->json(['message' => 'Database migration completed successfully!']);
})->middleware(['auth']);