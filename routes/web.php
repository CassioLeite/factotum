<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModulePlaceholderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::resource('contacts', ContactController::class);

Route::get('/modules/{module}', ModulePlaceholderController::class)
    ->whereIn('module', ['obligations', 'messages', 'templates', 'settings'])
    ->name('modules.show');
