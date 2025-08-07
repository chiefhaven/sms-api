<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});


Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/profile', [HomeController::class, 'index'])->name('profile');

Route::get('/clients', [ClientController::class, 'index'])->name('clients');

Auth::routes();

Route::get('/virtual-reality', [HomeController::class, 'index'])->name('virtual-reality');

Route::get('/rtl', [HomeController::class, 'index'])->name('rtl');

Route::get('/profile-static', [HomeController::class, 'index'])->name('profile-static');

Route::get('/sign-in-static', [HomeController::class, 'index'])->name('sign-in-static');

Route::get('/sign-up-static', [HomeController::class, 'index'])->name('sign-up-static');

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