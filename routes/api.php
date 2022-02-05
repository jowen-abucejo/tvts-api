<?php

use App\Http\Controllers\ExtraPropertyController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\ViolationTypeController;
use App\Http\Controllers\ViolatorController;
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

Route::middleware(['auth:api'])->prefix(env('API_VERSION', 'v1'))->group(function () {
    Route::get('users/', [UserController::class, 'index']);
    Route::post('users/new/{violator_id?}', [UserController::class, 'store']);  
    Route::post('users/user', [UserController::class, 'update']);  
    Route::post('users/user/confirm-password', [UserController::class, 'checkPasswordMatch']);  
    Route::post('users/user/logout', [UserController::class, 'logout']);  

    Route::get('violations/', [ViolationController::class, 'index']);
    Route::get('violations/violation', [ViolationController::class, 'show']);
    Route::post('violations/new', [ViolationController::class, 'store']);

    Route::get('violations/types', [ViolationTypeController::class, 'index']);
    Route::get('violations/types/by-vehicle-types', [ViolationController::class, 'groupByVehicleType']);

    Route::post('violations/types/new', [ViolationTypeController::class, 'store']);
    Route::get('violations/count/by-ticket', [ViolationController::class, 'groupByAndCount']);

    Route::get('violators/', [ViolatorController::class, 'index']);
    Route::post('violators/violator/', [ViolatorController::class, 'show']);
    Route::get('violators/violator/{id}', [ViolatorController::class, 'show']);
    Route::get('violators/count/by-ticket', [ViolatorController::class, 'groupByAndCount']);

    Route::get('tickets/', [TicketController::class, 'index']);  
    Route::post('tickets/new', [TicketController::class, 'store']);
    
    Route::get('tickets/ticket/{ticket_number?}', [TicketController::class, 'show']);
    Route::get('tickets/count/by-date', [TicketController::class, 'groupByDateAndCount']); 

    Route::post('tickets/email-qr/{ticket_number}', [TicketController::class, 'emailQRCode']);
    
    Route::get('forms/ext/fields/{property_owner?}', [ExtraPropertyController::class, 'index']);  

    Route::get('resources/image/{image_path}', [TicketController::class, 'testShowImage']);  


});

//!START OF TEST ROUTES
//FOR TESTING ONLY
Route::get('users', [UserController::class, 'index']);  
Route::get('violations/new', [ViolationController::class, 'store']);
Route::get('violations', [ViolationController::class, 'index']);
Route::get('violations/types/new', [ViolationTypeController::class, 'store']);
Route::get('violations/types/by-vehicle-types', [ViolationController::class, 'groupByVehicleType']);
Route::get('tickets/', [TicketController::class, 'index']);  
Route::get('tickets/ticket/{ticket_number?}', [TicketController::class, 'show']);
Route::get('tickets/count/by-date', [TicketController::class, 'groupByDateAndCount']);  
Route::get('tickets/ticket/sendSMS', [TicketController::class, 'edit']);  
Route::get('tickets/email-qr/{ticket_number?}', [TicketController::class, 'emailQRCode'] );
Route::get('resources/image/{image_path}', [TicketController::class, 'testShowImage']);  
Route::get('violators/', [ViolatorController::class, 'index']);
Route::get('violators/violator/{id}', [ViolatorController::class, 'show']);  
Route::get('violations/count/by-ticket', [ViolationController::class, 'groupByAndCount']);
Route::get('violators/count/by-ticket', [ViolatorController::class, 'groupByAndCount']);
Route::get('forms/ext/fields/{property_owner?}', [ExtraPropertyController::class, 'index']);  

//!END OF TEST ROUTES


Route::post('users/user/login', [UserController::class, 'login']);  //issuing access token



