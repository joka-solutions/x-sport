<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
$user = Session::get('user');

if ($user) {
    Auth::login($user);
    // استعادة جلسة المستخدم
}

Route::get('/', function () {
    return view('welcome');
});

Route::get('/session', function () {
    return \auth()->user();
});

Route::get('/auth/google', [\App\Http\Controllers\Socialite\SocialiteController::class, 'redirectToGoogle'])->name('redirectToGoogle');
Route::get('/auth/google/callback', [\App\Http\Controllers\Socialite\SocialiteController::class, 'handleGoogleCallback'])->name('handleGoogleCallback');
//Route::get('auth/google/callback', function () {
//    $user = Socialite::driver('google')->user();
//
//    $email = $user->getEmail();
//    return $email;
//})->name('auth.google.callback');


