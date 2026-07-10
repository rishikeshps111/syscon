<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GeneralSettingController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\VehicleImageReadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/free-no', [GeneralSettingController::class, 'freeNo'])->name('free-no');
    Route::post('/read-vehicle-image', [VehicleImageReadController::class, 'read']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::prefix('driver/trips')->name('driver.trips.')->group(function () {
            Route::get('/today', [TripController::class, 'driverToday'])->name('today');
            Route::get('/{tripSheetEntry}', [TripController::class, 'driverShow'])->name('show');
            Route::get('/', [TripController::class, 'driverIndex'])->name('index');
        });

        Route::prefix('trips')->name('trips.')->group(function () {
            Route::get('/today', [TripController::class, 'today'])->name('today');
            Route::post('/verify-driver', [TripController::class, 'verifyDriver'])->name('verify-driver');
            Route::post('/start-verification', [TripController::class, 'startVerification'])->name('start-verification');
            Route::get('/{tripSheetEntry}', [TripController::class, 'show'])->name('show');
            Route::get('/', [TripController::class, 'index'])->name('index');
        });

        Route::prefix('vehicles')->name('vehicles.')->group(function () {
            Route::get('/detail', [VehicleController::class, 'detail'])->name('detail');
        });
    });
});
