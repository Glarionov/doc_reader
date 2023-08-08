<?php

use App\Http\Controllers\DataRowController;
use App\Http\Controllers\SavingProcessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/data-rows', [DataRowController::class, 'index'])->name('data-rows');
Route::get('/data-rows/reading-status', [SavingProcessController::class, 'getReadingStatus'])->name('reading-status');
