<?php

use App\Http\Controllers\PTController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\HomeController;


// WebAuthn login & first-time setup — works for both guests and authenticated users
Route::post('/webauthn/login-options', [App\Http\Controllers\WebAuthnController::class, 'loginOptions'])->middleware('throttle:webauthn.options')->name('webauthn.login.options');
Route::post('/webauthn/available-methods', [App\Http\Controllers\WebAuthnController::class, 'availableMethods'])->middleware('throttle:webauthn.options')->name('webauthn.available.methods');
Route::post('/webauthn/login', [App\Http\Controllers\WebAuthnController::class, 'login'])->middleware('throttle:login')->name('webauthn.login');
Route::post('/webauthn/setup-options', [App\Http\Controllers\WebAuthnController::class, 'setupOptions'])->middleware('throttle:webauthn.options')->name('webauthn.setup.options');
Route::post('/webauthn/setup-register', [App\Http\Controllers\WebAuthnController::class, 'setupRegister'])->middleware('throttle:login')->name('webauthn.setup.register');

// Web Push Notification Subscriptions & Testing
Route::get('/push/public-key', [App\Http\Controllers\PushSubscriptionController::class, 'getPublicKey'])->name('push.public_key');
Route::post('/push/subscribe', [App\Http\Controllers\PushSubscriptionController::class, 'subscribe'])->middleware('auth')->name('push.subscribe');
Route::post('/push/unsubscribe', [App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe'])->middleware('auth')->name('push.unsubscribe');
Route::post('/push/test', [App\Http\Controllers\PushSubscriptionController::class, 'sendTest'])->middleware('auth')->name('push.test');


// Intro page should always show, with private no-cache to evaluate destination correctly
Route::get('/', function () {
    return response()
        ->view('intro')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
})->name('intro');

// Offline page for PWA
Route::get('/offline', function () {
    return response()->view('offline', [])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->name('offline');

// Service Worker with strict no-cache headers for instant mobile updates
Route::get('/sw.js', function () {
    $swPath = public_path('sw.js');
    if (!file_exists($swPath)) {
        abort(404);
    }
    $content = file_get_contents($swPath);
    return response($content, 200, [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
        'Service-Worker-Allowed' => '/',
    ]);
})->name('pwa.sw');
