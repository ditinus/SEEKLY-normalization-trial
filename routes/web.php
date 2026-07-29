<?php

use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('import.create');
});

Route::get('/import', [ImportController::class, 'create'])->name('import.create');
Route::post('/import', [ImportController::class, 'store'])->name('import.store');
