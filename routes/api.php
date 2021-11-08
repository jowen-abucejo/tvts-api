<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\ViolationTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    Route::get('users/', [UserController::class, 'index']);
    Route::post('users/new', [UserController::class, 'store']);  


    Route::get('violations/', [ViolationController::class, 'index']);
    Route::get('violations/{vehicleType}', [ViolationController::class, 'getByVehicleType']);
    Route::post('violations/new', [ViolationController::class, 'store']);

    Route::get('violations/types', [ViolationTypeController::class, 'index']);
    Route::get('violations/types/vehicle-types', [ViolationController::class, 'groupByVehicleType']);

    Route::post('violations/types/new', [ViolationTypeController::class, 'store']);
});


Route::get('users/new', [UserController::class, 'store']);  
Route::get('violations/new', [ViolationController::class, 'store']);
Route::get('violations/types/new', [ViolationTypeController::class, 'store']);

