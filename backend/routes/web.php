<?php

use Illuminate\Support\Facades\Route;

// Override any conflicting root routes by defining this first
Route::get('/', function () {
    // Check if user is authenticated, redirect to appropriate panel
    if (auth()->check()) {
        return redirect('/user');
    }
    return redirect('/user/login');
})->name('home');
