<?php

use App\Http\Controllers\DatabaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [DatabaseController::class, 'index'])->name('index');
Route::post('/connect', [DatabaseController::class, 'connect'])->name('connect');
Route::post('/execute-query', [DatabaseController::class, 'executeQuery'])->name('execute-query');
