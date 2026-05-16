<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchLocationController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\HrmsDocumentTypeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PrefixController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\RouteStopController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\ShiftSettingController;
use App\Http\Controllers\StaffDocumentController;
use App\Http\Controllers\StaffManagementController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\TripSetupController;
use App\Http\Controllers\VehicleClassificationController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/remove-avatar', [ProfileController::class, 'removeAvatar'])->name('profile.remove-avatar');

    Route::post('/prefixes/status', [PrefixController::class, 'status'])->name('prefixes.status');
    Route::post('/prefixes/export', [PrefixController::class, 'export'])
        ->name('prefixes.export');
    Route::resource('prefixes', PrefixController::class)->except(['edit', 'show']);

    Route::post('/states/status', [StateController::class, 'status'])->name('states.status');
    Route::post('/states/export', [StateController::class, 'export'])
        ->name('states.export');
    Route::resource('states', StateController::class)->except(['edit', 'show']);

    Route::post('/districts/status', [DistrictController::class, 'status'])->name('districts.status');
    Route::post('/districts/export', [DistrictController::class, 'export'])
        ->name('districts.export');
    Route::resource('districts', DistrictController::class)->except(['edit', 'show']);

    Route::get('/locations/districts-by-state', [LocationController::class, 'districtsByState'])
        ->name('locations.districts-by-state');
    Route::post('/locations/status', [LocationController::class, 'status'])->name('locations.status');
    Route::post('/locations/export', [LocationController::class, 'export'])
        ->name('locations.export');
    Route::resource('locations', LocationController::class)->except(['edit', 'show']);

    Route::post('/service-types/status', [ServiceTypeController::class, 'status'])->name('service-types.status');
    Route::post('/service-types/export', [ServiceTypeController::class, 'export'])
        ->name('service-types.export');
    Route::resource('service-types', ServiceTypeController::class)->except(['edit', 'show']);

    Route::post('/vehicle-classifications/status', [VehicleClassificationController::class, 'status'])->name('vehicle-classifications.status');
    Route::post('/vehicle-classifications/export', [VehicleClassificationController::class, 'export'])
        ->name('vehicle-classifications.export');
    Route::resource('vehicle-classifications', VehicleClassificationController::class)->except(['edit', 'show']);

    Route::post('/document-types/status', [DocumentTypeController::class, 'status'])->name('document-types.status');
    Route::post('/document-types/export', [DocumentTypeController::class, 'export'])
        ->name('document-types.export');
    Route::resource('document-types', DocumentTypeController::class)->except(['edit', 'show']);

    Route::post('/depots/status', [DepotController::class, 'status'])->name('depots.status');
    Route::post('/depots/export', [DepotController::class, 'export'])
        ->name('depots.export');
    Route::resource('depots', DepotController::class)->except(['edit', 'show']);

    Route::post('/routes/status', [RouteController::class, 'status'])->name('routes.status');
    Route::post('/routes/export', [RouteController::class, 'export'])
        ->name('routes.export');
    Route::get('/routes/{route}/preview', [RouteController::class, 'preview'])->name('routes.preview');
    Route::get('/routes/{route}/preview/export', [RouteController::class, 'previewExport'])->name('routes.preview.export');
    Route::get('/routes/{route}/stops', [RouteStopController::class, 'index'])->name('routes.stops.index');
    Route::get('/routes/{route}/stops/create', [RouteStopController::class, 'create'])->name('routes.stops.create');
    Route::post('/routes/{route}/stops', [RouteStopController::class, 'store'])->name('routes.stops.store');
    Route::post('/routes/{route}/stops/export', [RouteStopController::class, 'export'])->name('routes.stops.export');
    Route::put('/route-stops/{route_stop}', [RouteStopController::class, 'update'])->name('route-stops.update');
    Route::patch('/route-stops/{route_stop}', [RouteStopController::class, 'update']);
    Route::delete('/route-stops/{route_stop}', [RouteStopController::class, 'destroy'])->name('route-stops.destroy');
    Route::resource('routes', RouteController::class)->except(['edit', 'show']);

    Route::post('/trip-setups/status', [TripSetupController::class, 'status'])->name('trip-setups.status');
    Route::post('/trip-setups/export', [TripSetupController::class, 'export'])
        ->name('trip-setups.export');
    Route::resource('trip-setups', TripSetupController::class)->except(['edit', 'show']);

    Route::get('/branch-locations/districts-by-state', [BranchLocationController::class, 'districtsByState'])
        ->name('branch-locations.districts-by-state');
    Route::get('/branch-locations/locations-by-district', [BranchLocationController::class, 'locationsByDistrict'])
        ->name('branch-locations.locations-by-district');
    Route::post('/branch-locations/status', [BranchLocationController::class, 'status'])
        ->name('branch-locations.status');
    Route::post('/branch-locations/export', [BranchLocationController::class, 'export'])
        ->name('branch-locations.export');
    Route::resource('branch-locations', BranchLocationController::class)->except(['edit', 'show']);

    Route::post('/departments/status', [DepartmentController::class, 'status'])
        ->name('departments.status');
    Route::post('/departments/export', [DepartmentController::class, 'export'])
        ->name('departments.export');
    Route::resource('departments', DepartmentController::class)->except(['edit', 'show']);

    Route::post('/levels/status', [LevelController::class, 'status'])
        ->name('levels.status');
    Route::post('/levels/export', [LevelController::class, 'export'])
        ->name('levels.export');
    Route::resource('levels', LevelController::class)->except(['edit', 'show']);

    Route::post('/designations/status', [DesignationController::class, 'status'])
        ->name('designations.status');
    Route::post('/designations/export', [DesignationController::class, 'export'])
        ->name('designations.export');
    Route::resource('designations', DesignationController::class)->except(['edit', 'show']);

    Route::post('/hrms-document-types/status', [HrmsDocumentTypeController::class, 'status'])
        ->name('hrms-document-types.status');
    Route::post('/hrms-document-types/export', [HrmsDocumentTypeController::class, 'export'])
        ->name('hrms-document-types.export');
    Route::resource('hrms-document-types', HrmsDocumentTypeController::class)->except(['edit', 'show']);

    Route::post('/leave-types/status', [LeaveTypeController::class, 'status'])
        ->name('leave-types.status');
    Route::post('/leave-types/export', [LeaveTypeController::class, 'export'])
        ->name('leave-types.export');
    Route::resource('leave-types', LeaveTypeController::class);

    Route::post('/shift-settings/status', [ShiftSettingController::class, 'status'])
        ->name('shift-settings.status');
    Route::post('/shift-settings/export', [ShiftSettingController::class, 'export'])
        ->name('shift-settings.export');
    Route::resource('shift-settings', ShiftSettingController::class)->except(['show']);

    Route::get('/holidays/calendar', [HolidayController::class, 'calendar'])
        ->name('holidays.calendar');
    Route::get('/holidays/calendar-view', [HolidayController::class, 'calendarView'])
        ->name('holidays.calendar-view');
    Route::post('/holidays/status', [HolidayController::class, 'status'])
        ->name('holidays.status');
    Route::post('/holidays/export', [HolidayController::class, 'export'])
        ->name('holidays.export');
    Route::resource('holidays', HolidayController::class);

    Route::post('/staff-management/status', [StaffManagementController::class, 'status'])
        ->name('staff-management.status');
    Route::post('/staff-management/export', [StaffManagementController::class, 'export'])
        ->name('staff-management.export');
    Route::get('/staff-management/{staff_management}/download-pdf', [StaffManagementController::class, 'downloadPdf'])
        ->name('staff-management.download-pdf');
    Route::get('/staff-management/{staff}/documents', [StaffDocumentController::class, 'index'])
        ->name('staff-management.documents.index');
    Route::post('/staff-management/{staff}/documents', [StaffDocumentController::class, 'store'])
        ->name('staff-management.documents.store');
    Route::get('/staff-documents/{staffDocument}/download', [StaffDocumentController::class, 'download'])
        ->name('staff-documents.download');
    Route::get('/staff-documents/{staffDocument}/preview', [StaffDocumentController::class, 'preview'])
        ->name('staff-documents.preview');
    Route::delete('/staff-documents/{staffDocument}', [StaffDocumentController::class, 'destroy'])
        ->name('staff-documents.destroy');
    Route::resource('staff-management', StaffManagementController::class);
});

Route::get('/storage-link', function () {
    Artisan::call('storage:link');
    return "Storage link created successfully!";
});

Route::get('/clear-all', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');

    return "All cache cleared!";
});

Route::get('system/migrate/{filename}', function ($filename) {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/' . $filename . '.php',
        '--force' => true,
    ]);
    return '<pre>' . Artisan::output() . '</pre>';
});

Route::get('system/migrate-fresh', function () {
    Artisan::call('migrate:fresh', ['--seed' => true]);
    return  "Database migrated fresh and seeded successfully!";
})->name('system.migrate-fresh');

require __DIR__ . '/auth.php';
