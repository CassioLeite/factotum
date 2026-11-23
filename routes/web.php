<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Controllers\ObligationController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::resource('contacts', ContactController::class);
Route::resource('obligations', ObligationController::class);
Route::resource('templates', MessageTemplateController::class);

Route::get('/modules/{module}', ModulePlaceholderController::class)
    ->whereIn('module', ['messages', 'settings'])
    ->name('modules.show');
