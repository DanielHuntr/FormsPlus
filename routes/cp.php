<?php

use App\FormsPlus\Http\Controllers\EmailTemplateController;
use App\FormsPlus\Http\Controllers\FormsController;
use App\FormsPlus\Http\Controllers\SettingsController;
use App\FormsPlus\Http\Controllers\StylesController;
use App\FormsPlus\Http\Controllers\SubmissionsController;
use Illuminate\Support\Facades\Route;

Route::prefix('forms-plus')->name('forms-plus.')->group(function () {
    Route::get('/list', [FormsController::class, 'listJson'])->name('list');
    Route::get('/', [FormsController::class, 'index'])->name('index');
    Route::post('/', [FormsController::class, 'store'])->name('store');

    // Default email templates (must be above /{handle} routes)
    Route::get('/email-templates', [EmailTemplateController::class, 'showDefaults'])->name('email-templates');
    Route::get('/email-templates/{type}', [EmailTemplateController::class, 'getTemplate'])->name('email-templates.get');
    Route::post('/email-templates/{type}', [EmailTemplateController::class, 'saveTemplate'])->name('email-templates.save');

    Route::get('/{handle}/edit', [FormsController::class, 'edit'])->name('edit');
    Route::get('/{handle}/fields', [FormsController::class, 'fields'])->name('fields');
    Route::post('/{handle}/fields', [FormsController::class, 'saveFields'])->name('fields.save');
    Route::delete('/{handle}', [FormsController::class, 'destroy'])->name('destroy');

    Route::get('/{handle}/submissions', [SubmissionsController::class, 'index'])->name('submissions');
    Route::delete('/{handle}/submissions/{id}', [SubmissionsController::class, 'destroy'])->name('submissions.destroy');
    Route::get('/{handle}/submissions/export', [SubmissionsController::class, 'export'])->name('submissions.export');

    Route::get('/{handle}/settings', [SettingsController::class, 'show'])->name('settings');
    Route::post('/{handle}/settings', [SettingsController::class, 'save'])->name('settings.save');

    // Global theme / styles
    Route::get('/theme', [StylesController::class, 'showPage'])->name('theme');
    Route::get('/theme/api', [StylesController::class, 'show'])->name('styles.api');
    Route::post('/theme/api', [StylesController::class, 'save'])->name('styles.save');

    // Per-form email templates
    Route::get('/{handle}/email/{type}', [EmailTemplateController::class, 'getFormTemplate'])->name('email.get');
    Route::post('/{handle}/email/{type}', [EmailTemplateController::class, 'saveFormTemplate'])->name('email.save');
});
