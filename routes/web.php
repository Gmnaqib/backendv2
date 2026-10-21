<?php

use App\Http\Controllers\SwaggerSpecController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/swagger', [SwaggerSpecController::class, 'ui']);
Route::get('/swagger/spec', [SwaggerSpecController::class, 'spec']);
