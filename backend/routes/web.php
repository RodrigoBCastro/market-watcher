<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', function () {
    $indexFile = public_path('index.html');

    if (file_exists($indexFile)) {
        return response()->file($indexFile, [
            'Content-Type' => 'text/html',
        ]);
    }

    return view('welcome');
})->where('any', '^(?!api).*$');
