<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchLocationController;
use App\Http\Controllers\ComplaintCategoryController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ControllerDocumentController;
use App\Http\Controllers\ControllerManagementController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DriverDocumentController;
use App\Http\Controllers\DriverManagementController;
use App\Http\Controllers\HrmsDocumentTypeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OemBankDetailController;
use App\Http\Controllers\OemController;
use App\Http\Controllers\OemDocumentController;
use App\Http\Controllers\OemStateMappingController;
use App\Http\Controllers\OemTypeController;
use App\Http\Controllers\PrefixController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\RouteStopController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\ShiftSettingController;
use App\Http\Controllers\StaffDocumentController;
use App\Http\Controllers\StaffManagementController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\SupervisorDocumentController;
use App\Http\Controllers\SupervisorManagementController;
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

    Route::post('/complaint-categories/status', [ComplaintCategoryController::class, 'status'])->name('complaint-categories.status');
    Route::post('/complaint-categories/export', [ComplaintCategoryController::class, 'export'])
        ->name('complaint-categories.export');
    Route::resource('complaint-categories', ComplaintCategoryController::class)->except(['edit', 'show']);

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

    Route::post('/oem-types/status', [OemTypeController::class, 'status'])->name('oem-types.status');
    Route::post('/oem-types/export', [OemTypeController::class, 'export'])
        ->name('oem-types.export');
    Route::resource('oem-types', OemTypeController::class)->except(['edit', 'show']);

    Route::post('/oems/export', [OemController::class, 'export'])
        ->name('oems.export');
    Route::post('/oems/{oem}/verify', [OemController::class, 'verify'])
        ->name('oems.verify');
    Route::post('/oems/{oem}/change-status', [OemController::class, 'changeStatus'])
        ->name('oems.change-status');
    Route::get('/oems/{oem}/download-pdf', [OemController::class, 'downloadPdf'])
        ->name('oems.download-pdf');
    Route::get('/oems/{oem}/documents', [OemDocumentController::class, 'index'])
        ->name('oems.documents.index');
    Route::post('/oems/{oem}/documents', [OemDocumentController::class, 'store'])
        ->name('oems.documents.store');
    Route::get('/oems/{oem}/bank-details', [OemBankDetailController::class, 'index'])
        ->name('oems.bank-details.index');
    Route::post('/oems/{oem}/bank-details', [OemBankDetailController::class, 'store'])
        ->name('oems.bank-details.store');
    Route::get('/oems/{oem}/state-mappings', [OemStateMappingController::class, 'index'])
        ->name('oems.state-mappings.index');
    Route::post('/oems/{oem}/state-mappings', [OemStateMappingController::class, 'store'])
        ->name('oems.state-mappings.store');
    Route::put('/oem-bank-details/{oemBankDetail}', [OemBankDetailController::class, 'update'])
        ->name('oem-bank-details.update');
    Route::post('/oem-bank-details/{oemBankDetail}/make-primary', [OemBankDetailController::class, 'makePrimary'])
        ->name('oem-bank-details.make-primary');
    Route::delete('/oem-bank-details/{oemBankDetail}', [OemBankDetailController::class, 'destroy'])
        ->name('oem-bank-details.destroy');
    Route::put('/oem-state-mappings/{oemStateMapping}', [OemStateMappingController::class, 'update'])
        ->name('oem-state-mappings.update');
    Route::post('/oem-state-mappings/{oemStateMapping}/make-primary', [OemStateMappingController::class, 'makePrimary'])
        ->name('oem-state-mappings.make-primary');
    Route::delete('/oem-state-mappings/{oemStateMapping}', [OemStateMappingController::class, 'destroy'])
        ->name('oem-state-mappings.destroy');
    Route::get('/oem-documents/{oemDocument}/download', [OemDocumentController::class, 'download'])
        ->name('oem-documents.download');
    Route::get('/oem-documents/{oemDocument}/preview', [OemDocumentController::class, 'preview'])
        ->name('oem-documents.preview');
    Route::delete('/oem-documents/{oemDocument}', [OemDocumentController::class, 'destroy'])
        ->name('oem-documents.destroy');
    Route::resource('oems', OemController::class);

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
    Route::get('/staff-management/districts-by-state', [StaffManagementController::class, 'districtsByState'])
        ->name('staff-management.districts-by-state');
    Route::get('/staff-management/locations-by-district', [StaffManagementController::class, 'locationsByDistrict'])
        ->name('staff-management.locations-by-district');
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

    Route::post('/controller-management/status', [ControllerManagementController::class, 'status'])
        ->name('controller-management.status');
    Route::post('/controller-management/export', [ControllerManagementController::class, 'export'])
        ->name('controller-management.export');
    Route::get('/controller-management/districts-by-state', [ControllerManagementController::class, 'districtsByState'])
        ->name('controller-management.districts-by-state');
    Route::get('/controller-management/locations-by-district', [ControllerManagementController::class, 'locationsByDistrict'])
        ->name('controller-management.locations-by-district');
    Route::get('/controller-management/{controller}/documents', [ControllerDocumentController::class, 'index'])
        ->name('controller-management.documents.index');
    Route::post('/controller-management/{controller}/documents', [ControllerDocumentController::class, 'store'])
        ->name('controller-management.documents.store');
    Route::get('/controller-management/{controller_management}/download-pdf', [ControllerManagementController::class, 'downloadPdf'])
        ->name('controller-management.download-pdf');
    Route::get('/controller-documents/{controllerDocument}/download', [ControllerDocumentController::class, 'download'])
        ->name('controller-documents.download');
    Route::get('/controller-documents/{controllerDocument}/preview', [ControllerDocumentController::class, 'preview'])
        ->name('controller-documents.preview');
    Route::delete('/controller-documents/{controllerDocument}', [ControllerDocumentController::class, 'destroy'])
        ->name('controller-documents.destroy');
    Route::resource('controller-management', ControllerManagementController::class);

    Route::post('/supervisor-management/status', [SupervisorManagementController::class, 'status'])
        ->name('supervisor-management.status');
    Route::post('/supervisor-management/export', [SupervisorManagementController::class, 'export'])
        ->name('supervisor-management.export');
    Route::get('/supervisor-management/districts-by-state', [SupervisorManagementController::class, 'districtsByState'])
        ->name('supervisor-management.districts-by-state');
    Route::get('/supervisor-management/locations-by-district', [SupervisorManagementController::class, 'locationsByDistrict'])
        ->name('supervisor-management.locations-by-district');
    Route::get('/supervisor-management/{supervisor}/documents', [SupervisorDocumentController::class, 'index'])
        ->name('supervisor-management.documents.index');
    Route::post('/supervisor-management/{supervisor}/documents', [SupervisorDocumentController::class, 'store'])
        ->name('supervisor-management.documents.store');
    Route::get('/supervisor-management/{supervisor_management}/download-pdf', [SupervisorManagementController::class, 'downloadPdf'])
        ->name('supervisor-management.download-pdf');
    Route::get('/supervisor-documents/{supervisorDocument}/download', [SupervisorDocumentController::class, 'download'])
        ->name('supervisor-documents.download');
    Route::get('/supervisor-documents/{supervisorDocument}/preview', [SupervisorDocumentController::class, 'preview'])
        ->name('supervisor-documents.preview');
    Route::delete('/supervisor-documents/{supervisorDocument}', [SupervisorDocumentController::class, 'destroy'])
        ->name('supervisor-documents.destroy');
    Route::resource('supervisor-management', SupervisorManagementController::class);

    Route::get('/driver-management/districts-by-state', [DriverManagementController::class, 'districtsByState'])
        ->name('driver-management.districts-by-state');
    Route::get('/driver-management/locations-by-district', [DriverManagementController::class, 'locationsByDistrict'])
        ->name('driver-management.locations-by-district');
    Route::post('/driver-management/status', [DriverManagementController::class, 'status'])
        ->name('driver-management.status');
    Route::post('/driver-management/export', [DriverManagementController::class, 'export'])
        ->name('driver-management.export');
    Route::get('/driver-management/{driver_management}/download-pdf', [DriverManagementController::class, 'downloadPdf'])
        ->name('driver-management.download-pdf');
    Route::get('/driver-management/{driver}/documents', [DriverDocumentController::class, 'index'])
        ->name('driver-management.documents.index');
    Route::post('/driver-management/{driver}/documents', [DriverDocumentController::class, 'store'])
        ->name('driver-management.documents.store');
    Route::get('/driver-documents/{driverDocument}/download', [DriverDocumentController::class, 'download'])
        ->name('driver-documents.download');
    Route::get('/driver-documents/{driverDocument}/preview', [DriverDocumentController::class, 'preview'])
        ->name('driver-documents.preview');
    Route::delete('/driver-documents/{driverDocument}', [DriverDocumentController::class, 'destroy'])
        ->name('driver-documents.destroy');
    Route::resource('driver-management', DriverManagementController::class);

    Route::get('/complaints/users-by-role', [ComplaintController::class, 'usersByRole'])
        ->name('complaints.users-by-role');
    Route::post('/complaints/export', [ComplaintController::class, 'export'])
        ->name('complaints.export');
    Route::post('/complaints/{complaint}/change-status', [ComplaintController::class, 'changeStatus'])
        ->name('complaints.change-status');
    Route::post('/complaints/{complaint}/assign-action', [ComplaintController::class, 'assignAction'])
        ->name('complaints.assign-action');
    Route::resource('complaints', ComplaintController::class);
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
