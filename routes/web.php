<?php

use Illuminate\Support\Facades\Route;

// SPA catch-all: every non-API route returns the Vue shell so Vue Router can take over.
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '^(?!api|sanctum|storage|up|build).*$');
