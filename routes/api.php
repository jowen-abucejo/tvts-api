<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ExtraPropertyController;
use App\Http\Controllers\PaymentController;
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

Route::middleware("auth:api")->get("/user", function (Request $request) {
    return $request->user();
});

Route::middleware(["auth:api"])
    ->prefix(env("API_VERSION"))
    ->group(function () {
        Route::get("users/", [UserController::class, "index"]);
        Route::post("users/new/", [UserController::class, "store"]);
        Route::get("users/user/{user_id?}", [UserController::class, "show"]);
        Route::put("users/user/{user_id?}", [UserController::class, "update"]);
        Route::put("users/reset/{user_id?}", [UserController::class, "edit"]);
        Route::delete("users/user/delete/{user_id}", [
            UserController::class,
            "destroy",
        ]);
        Route::post("users/user/confirm-password", [
            UserController::class,
            "checkPasswordMatch",
        ]); //!
        Route::post("users/user/logout", [UserController::class, "logout"]); //!

        Route::get("violations/", [ViolationController::class, "index"]);
        Route::post("violations/new", [ViolationController::class, "store"]);
        Route::get("violations/violation/{violation_id}/{violation_type_id}", [
            ViolationController::class,
            "show",
        ]);
        Route::put("violations/violation/{violation_id}/{violation_type_id}", [
            ViolationController::class,
            "update",
        ]);
        Route::delete(
            "violations/violation/delete/{violation_id}/{violation_type_id}",
            [ViolationController::class, "destroy"]
        );
        Route::patch(
            "violations/violation/toggle/{violation_id}/{violation_type_id}",
            [ViolationController::class, "edit"]
        );
        Route::get("violations/types/by-vehicle-types", [
            ViolationController::class,
            "groupByVehicleType",
        ]); //!
        Route::get("violations/count/by-ticket", [
            ViolationController::class,
            "countEachTickets",
        ]);

        Route::get("violators/", [ViolatorController::class, "index"]);
        Route::post("violators/violator/", [ViolatorController::class, "show"]); //!GET METHOD NOT WORKING WHEN SUBMITTING FORM DATA
        Route::put("violators/violator/{violator_id}", [
            ViolatorController::class,
            "update",
        ]);
        Route::get("violators/count/by-ticket", [
            ViolatorController::class,
            "countEachTickets",
        ]);

        Route::get("tickets/", [TicketController::class, "index"]);
        Route::post("tickets/new", [TicketController::class, "store"]); //!
        Route::get("tickets/ticket/{ticket_number?}", [
            TicketController::class,
            "show",
        ]); //!
        Route::put("tickets/ticket/{ticket_id}", [
            TicketController::class,
            "update",
        ]);
        Route::delete("tickets/ticket/delete/{ticket_id}", [
            TicketController::class,
            "destroy",
        ]);
        Route::get("tickets/count/by-date", [
            TicketController::class,
            "dailyCount",
        ]); //!
        Route::post("tickets/email-qr/{ticket_number}", [
            TicketController::class,
            "emailQRCode",
        ]); //!

        Route::get("payments/", [PaymentController::class, "index"]);
        Route::post("payments/new", [PaymentController::class, "store"]);
        Route::get("payments/payment/{payment_id}", [
            PaymentController::class,
            "show",
        ]);
        Route::put("payments/payment/{payment_id}", [
            PaymentController::class,
            "update",
        ]);
        Route::delete("payments/payment/delete/{payment_id}", [
            PaymentController::class,
            "destroy",
        ]);

        Route::get("forms/ext/input/fields/{property_owner?}", [
            ExtraPropertyController::class,
            "index",
        ]); //!
        Route::post("forms/ext/input/field/new", [
            ExtraPropertyController::class,
            "store",
        ]);
        Route::put("forms/ext/input/field/{extra_property_id}", [
            ExtraPropertyController::class,
            "update",
        ]);
        Route::get("forms/ext/input/field/{extra_property_id}", [
            ExtraPropertyController::class,
            "show",
        ]);
        Route::delete("forms/ext/input/field/delete/{extra_property_id}", [
            ExtraPropertyController::class,
            "destroy",
        ]);

        Route::get("resources/image/{image_path}", [
            TicketController::class,
            "showImage",
        ]); //!

        Route::get("activity-logs", [ActivityLogController::class, "index"]);
    });

Route::post("users/user/login", [UserController::class, "login"]); //issuing access token
