<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/com-run', function (Request $request) {

    // 1. SECURITY CHECK: Validate a secret key
    if ($request->query('key') !== 'rHdeXbsUHfg6HUPagLjdHrF4') {
        abort(403, 'Unauthorized access.');
    }

    // 2. GET COMMAND: Default to 'migrate', or allow custom commands
    $command = $request->query('cmd', 'migrate');

    try {
        // 3. RUN IT
        // Force is required for production
        Artisan::call($command, ['--force' => true]);
        return "<h3>Command '$command' executed successfully.</h3><pre>" . Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        return "<h3>Error:</h3><pre>" . $e->getMessage() . "</pre>";
    }
});
