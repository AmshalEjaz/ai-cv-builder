<?php

use App\Http\Controllers\CVController;
use App\Http\Controllers\TemplateController;
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
    Route::get('templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');
});

Route::get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = Auth::user();
    $cvCount = $user->cvs()->count();
    $recentCvs = $user->cvs()->with('template')->latest()->take(5)->get();

    return view('dashboard', compact('cvCount', 'recentCvs'));
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';