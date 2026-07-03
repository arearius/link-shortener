<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public short-link redirect. Registered last as a catch-all; the constraint
// excludes reserved single-segment paths (Filament panel `/app`, health `/up`)
// so they always reach their real handlers.
Route::get('/{code}', RedirectController::class)
    ->where('code', '(?!app$|up$)[A-Za-z0-9]+')
    ->name('short-link.redirect');
