<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app');
Route::view('/auth/{any?}', 'app')->where('any', '.*');
Route::view('/dashboard/{any?}', 'app')->where('any', '.*');
