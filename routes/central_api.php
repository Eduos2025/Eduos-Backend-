<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiOnboardingController;

/*
|--------------------------------------------------------------------------
| Central API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by bootstrap/app.php in the central context.
| They are prefixed with 'api/saas' and run on the central domain.
|
*/

Route::get('/plans', [ApiOnboardingController::class, 'plans'])->name('api.saas.plans');
Route::get('/check-subdomain', [ApiOnboardingController::class, 'checkSubdomain'])->name('api.saas.check_subdomain');
Route::post('/register', [ApiOnboardingController::class, 'register'])->name('api.saas.register');
Route::post('/verify-payment', [ApiOnboardingController::class, 'verifyPayment'])->name('api.saas.verify_payment');
Route::post('/process-trial', [ApiOnboardingController::class, 'processTrial'])->name('api.saas.process_trial');
