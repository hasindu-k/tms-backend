<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-migration', function () {
    Artisan::call('migrate', ['--force' => true]);
    return 'Migration run successfully: ' . Artisan::output();
});
