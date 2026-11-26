<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverManagementController;
use App\Http\Controllers\Api\V1\GeneralSettingController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\TripCancellationController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\TripDriverController;
use App\Http\Controllers\Api\V1\TripVehicleController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\VehicleDashboardImageReadController;
use App\Http\Controllers\Api\V1\VehicleImageReadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/free-no', [GeneralSettingController::class, 'freeNo'])->name('free-no');
    Route::post('/read-vehicle-image', [VehicleImageReadController::class, 'read']);
    Route::post('/read-vehicle-dashboard-image', [VehicleDashboardImageReadController::class, 'read']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::put('/device-token', [AuthController::class, 'updateDeviceToken'])->name('device-token.update');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/designations', [StaffController::class, 'designations'])->name('staff.designations');
        Route::get('/staff/{staff}/documents', [StaffController::class, 'documents'])->name('staff.documents');
        Route::get('/staff/{staff}/documents/{document}/preview', [StaffController::class, 'previewDocument'])->name('staff.documents.preview');
        Route::get('/staff/{staff}/documents/{document}/download', [StaffController::class, 'downloadDocument'])->name('staff.documents.download');
        Route::get('/staff/{staff}/letters', [StaffController::class, 'letters'])->name('staff.letters');
        Route::get('/staff/{staff}/letters/{letter}/preview', [StaffController::class, 'previewLetter'])->name('staff.letters.preview');
        Route::get('/staff/{staff}/letters/{letter}/download', [StaffController::class, 'downloadLetter'])->name('staff.letters.download');
        Route::get('/staff/{staff}/letters/{letter}', [StaffController::class, 'showLetter'])->name('staff.letters.show');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])->name('staff.show');

        Route::get('/drivers', [DriverManagementController::class, 'index'])->name('drivers.index');
        Route::get('/drivers/filter-options', [DriverManagementController::class, 'filterOptions'])->name('drivers.filter-options');
        Route::get('/drivers/{driver}/documents', [DriverManagementController::class, 'documents'])->name('drivers.documents');
        Route::get('/drivers/{driver}/documents/{document}/preview', [DriverManagementController::class, 'previewDocument'])->name('drivers.documents.preview');
        Route::get('/drivers/{driver}/documents/{document}/download', [DriverManagementController::class, 'downloadDocument'])->name('drivers.documents.download');
        Route::get('/drivers/{driver}/letters', [DriverManagementController::class, 'letters'])->name('drivers.letters');
        Route::get('/drivers/{driver}/letters/{letter}/preview', [DriverManagementController::class, 'previewLetter'])->name('drivers.letters.preview');
        Route::get('/drivers/{driver}/letters/{letter}/download', [DriverManagementController::class, 'downloadLetter'])->name('drivers.letters.download');
        Route::get('/drivers/{driver}/letters/{letter}', [DriverManagementController::class, 'showLetter'])->name('drivers.letters.show');
        Route::get('/drivers/{driver}/qr/download', [DriverManagementController::class, 'downloadQr'])->name('drivers.qr.download');
        Route::get('/drivers/{driver}/qr', [DriverManagementController::class, 'qr'])->name('drivers.qr');
        Route::get('/drivers/{driver}', [DriverManagementController::class, 'show'])->name('drivers.show');

        Route::prefix('driver/trips')->name('driver.trips.')->group(function () {
            Route::get('/today', [TripController::class, 'driverToday'])->name('today');
            Route::get('/{tripSheetEntry}', [TripController::class, 'driverShow'])->name('show');
            Route::get('/', [TripController::class, 'driverIndex'])->name('index');
        });

        Route::prefix('trips')->name('trips.')->group(function () {
            Route::get('/{tripSheetEntry}/available-drivers', [TripDriverController::class, 'available'])->name('available-drivers');
            Route::patch('/{tripSheetEntry}/driver', [TripDriverController::class, 'update'])->name('driver.update');
            Route::get('/{tripSheetEntry}/available-vehicles', [TripVehicleController::class, 'available'])->name('available-vehicles');
            Route::patch('/{tripSheetEntry}/vehicle', [TripVehicleController::class, 'update'])->name('vehicle.update');
            Route::patch('/{tripSheetEntry}/cancel', [TripCancellationController::class, 'cancel'])->name('cancel');
            Route::get('/today', [TripController::class, 'today'])->name('today');
            Route::get('/controllers', [TripController::class, 'controllers'])->name('controllers');
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
