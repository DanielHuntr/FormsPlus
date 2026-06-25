<?php

use App\FormsPlus\Http\Controllers\HandleRedirectController;
use App\FormsPlus\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::get('__forms/{handle}', [PublicFormController::class, 'render'])->name('forms-plus.render');
Route::get('__forms/{handle}/redirect', HandleRedirectController::class)->name('forms-plus.redirect');
