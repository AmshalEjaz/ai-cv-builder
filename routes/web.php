<?php

use App\Http\Controllers\CVController;
use App\Http\Controllers\TemplateController;
use App\Models\Template;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::resource('cvs', CVController::class);
    Route::post('cvs/{cv}/enhance', [CVController::class, 'enhanceDescription'])->name('cvs.enhance');
    Route::get('cvs/{cv}/download', [CVController::class, 'download'])->name('cvs.download');
    
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/manage', [TemplateController::class, 'manage'])->name('templates.manage');
    Route::get('templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
    Route::get('templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');
});

Route::get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = Auth::user();
    $cvCount = $user->cvs()->count();
    $recentCvs = $user->cvs()->with('template')->latest()->take(5)->get();
    $templates = Template::where('is_active', true)->latest()->get();

    return view('dashboard', compact('cvCount', 'recentCvs', 'templates'));
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';