<?php

use App\Http\Controllers\ExtraPropertyController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViolationController;
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

Route::middleware(['auth:api'])->prefix(env('API_VERSION'))->group(function () {
    Route::get('users/', [UserController::class, 'index']);
    Route::post('users/new/', [UserController::class, 'store']);  
    Route::post('users/user', [UserController::class, 'update']);  
    Route::post('users/user/confirm-password', [UserController::class, 'checkPasswordMatch']);  
    Route::post('users/user/logout', [UserController::class, 'logout']);  

    Route::get('violations/', [ViolationController::class, 'index']);
    Route::post('violations/new', [ViolationController::class, 'store']);
    Route::get('violations/violation/{violation_id}/{violation_type_id}', [ViolationController::class, 'show']);
    Route::put('violations/violation/{violation_id}/{violation_type_id}', [ViolationController::class, 'update']);
    Route::delete('violations/violation/delete/{violation_id}/{violation_type_id}', [ViolationController::class, 'destroy']);
    Route::patch('violations/violation/toggle/{violation_id}/{violation_type_id}', [ViolationController::class, 'edit']);
    Route::get('violations/types/by-vehicle-types', [ViolationController::class, 'groupByVehicleType']);


    Route::get('violations/count/by-ticket', [ViolationController::class, 'countEachTickets']);

    Route::get('violators/', [ViolatorController::class, 'index']);
    Route::post('violators/violator/', [ViolatorController::class, 'show']);//!GET METHOD NOT WORKING WHEN SUBMITTING FORM DATA
    Route::get('violators/violator/{id?}', [ViolatorController::class, 'show']);
    Route::put('violators/violator/{violator_id}', [ViolatorController::class, 'update']);
    Route::get('violators/count/by-ticket', [ViolatorController::class, 'countEachTickets']);

    Route::get('tickets/', [TicketController::class, 'index']);  
    Route::post('tickets/new', [TicketController::class, 'store']);
    Route::get('tickets/ticket/{ticket_number?}', [TicketController::class, 'show']);
    Route::put('tickets/ticket/{ticket_id}', [TicketController::class, 'update']);
    Route::delete('tickets/ticket/delete/{ticket_id}', [TicketController::class, 'destroy']);
    Route::get('tickets/count/by-date', [TicketController::class, 'dailyCount']); 
    Route::post('tickets/email-qr/{ticket_number}', [TicketController::class, 'emailQRCode']);
    
    Route::get('forms/ext/fields/{property_owner?}', [ExtraPropertyController::class, 'index']);  
    Route::get('resources/image/{image_path}', [TicketController::class, 'showImage']);  

});

//!START OF TEST ROUTES
//FOR TESTING ONLY
Route::get('users', [UserController::class, 'index']);  
Route::get('violations/new', [ViolationController::class, 'store']);
Route::get('violations', [ViolationController::class, 'index']);
Route::post('violators/violator/', [ViolatorController::class, 'show']);
Route::get('violations/violation/{violation_id}/{violation_type_id}', [ViolationController::class, 'show']);
Route::get('violations/types/by-vehicle-types', [ViolationController::class, 'groupByVehicleType']);
Route::get('tickets/', [TicketController::class, 'index']);  
Route::get('tickets/ticket/{ticket_number?}', [TicketController::class, 'show']);
Route::get('tickets/count/by-date', [TicketController::class, 'dailyCount']);  
Route::get('tickets/ticket/sendSMS', [TicketController::class, 'edit']);  
Route::get('tickets/email-qr/{ticket_number?}', [TicketController::class, 'emailQRCode'] );
Route::get('resources/image/{image_path}', [TicketController::class, 'testShowImage']);  
Route::get('violators/', [ViolatorController::class, 'index']);
Route::get('violators/violator/{id}', [ViolatorController::class, 'show']);  
Route::get('violations/count/by-ticket', [ViolationController::class, 'countEachTickets']);
Route::get('violators/count/by-ticket', [ViolatorController::class, 'countEachTickets']);
Route::get('forms/ext/fields/{property_owner?}', [ExtraPropertyController::class, 'index']);  

//!END OF TEST ROUTES


Route::post('users/user/login', [UserController::class, 'login']);  //issuing access token



