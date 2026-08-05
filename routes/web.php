<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BranchLocationController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ComplaintCategoryController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ControllerDocumentController;
use App\Http\Controllers\ControllerManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepotAssignmentController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DorAccountResponsibleController;
use App\Http\Controllers\DorKilometerLossReasonController;
use App\Http\Controllers\DorReportController;
use App\Http\Controllers\DriverDocumentController;
use App\Http\Controllers\DriverManagementController;
use App\Http\Controllers\FinancialYearSettingController;
use App\Http\Controllers\GeneratePaySlipController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HrLetterController;
use App\Http\Controllers\HrLetterTemplateController;
use App\Http\Controllers\HrmsDocumentTypeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\LicenseExpiryReportController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OemBankDetailController;
use App\Http\Controllers\OemController;
use App\Http\Controllers\OemDepotController;
use App\Http\Controllers\OemDocumentController;
use App\Http\Controllers\OemStateMappingController;
use App\Http\Controllers\OemTypeController;
use App\Http\Controllers\PrefixController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\RouteAssignmentController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\RouteScheduleController;
use App\Http\Controllers\RouteStopController;
use App\Http\Controllers\SalaryComponentController;
use App\Http\Controllers\SalaryFilesController;
use App\Http\Controllers\SalaryProcessingController;
use App\Http\Controllers\SalaryReportController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\ShiftSettingController;
use App\Http\Controllers\StaffDocumentController;
use App\Http\Controllers\StaffManagementController;
use App\Http\Controllers\SalaryTemplateController;
use App\Http\Controllers\SalaryArchiveController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\SupervisorDocumentController;
use App\Http\Controllers\SupervisorManagementController;
use App\Http\Controllers\TripAssignmentController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserLogController;
use App\Http\Controllers\VehicleAssignmentController;
use App\Http\Controllers\VehicleClassificationController;
use App\Http\Controllers\TripNatureController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleDocumentController;
use App\Http\Controllers\VehicleFuelLogController;
use App\Http\Controllers\VehicleMaintenanceLogController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/remove-avatar', [ProfileController::class, 'removeAvatar'])->name('profile.remove-avatar');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread-count');
    Route::get('/chat/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.conversations.show');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.messages.store');
    Route::post('/chat/conversations/{conversation}/seen', [ChatController::class, 'seen'])->name('chat.conversations.seen');

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

    Route::post('/trip-natures/status', [TripNatureController::class, 'status'])->name('trip-natures.status');
    Route::post('/trip-natures/export', [TripNatureController::class, 'export'])->name('trip-natures.export');
    Route::resource('trip-natures', TripNatureController::class)->except(['edit', 'show']);

    Route::post('/document-types/status', [DocumentTypeController::class, 'status'])->name('document-types.status');
    Route::post('/document-types/export', [DocumentTypeController::class, 'export'])
        ->name('document-types.export');
    Route::resource('document-types', DocumentTypeController::class)->except(['edit', 'show']);

    Route::post('/complaint-categories/status', [ComplaintCategoryController::class, 'status'])->name('complaint-categories.status');
    Route::post('/complaint-categories/export', [ComplaintCategoryController::class, 'export'])
        ->name('complaint-categories.export');
    Route::resource('complaint-categories', ComplaintCategoryController::class)->except(['edit', 'show']);

    Route::post('/dor-account-responsibles/status', [DorAccountResponsibleController::class, 'status'])->name('dor-account-responsibles.status');
    Route::post('/dor-account-responsibles/export', [DorAccountResponsibleController::class, 'export'])
        ->name('dor-account-responsibles.export');
    Route::resource('dor-account-responsibles', DorAccountResponsibleController::class)->except(['edit', 'show']);

    Route::post('/dor-kilometer-loss-reasons/status', [DorKilometerLossReasonController::class, 'status'])->name('dor-kilometer-loss-reasons.status');
    Route::post('/dor-kilometer-loss-reasons/export', [DorKilometerLossReasonController::class, 'export'])
        ->name('dor-kilometer-loss-reasons.export');
    Route::resource('dor-kilometer-loss-reasons', DorKilometerLossReasonController::class)->except(['edit', 'show']);

    Route::post('/depots/status', [DepotController::class, 'status'])->name('depots.status');
    Route::get('/depots/districts-by-state', [DepotController::class, 'districtsByState'])
        ->name('depots.districts-by-state');
    Route::get('/depots/locations-by-district', [DepotController::class, 'locationsByDistrict'])
        ->name('depots.locations-by-district');
    Route::get('/depots/{depot}/assignments', [DepotAssignmentController::class, 'depotIndex'])
        ->name('depots.assignments.index');
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
    Route::post('/routes/{route}/stops/reorder', [RouteStopController::class, 'reorder'])->name('routes.stops.reorder');
    Route::get('/routes/{route}/assignments', [RouteAssignmentController::class, 'index'])->name('routes.assignments.index');
    Route::get('/routes/{route}/assignments/create', [RouteAssignmentController::class, 'create'])->name('routes.assignments.create');
    Route::post('/routes/{route}/assignments', [RouteAssignmentController::class, 'store'])->name('routes.assignments.store');
    Route::put('/route-assignments/{routeAssignment}', [RouteAssignmentController::class, 'update'])->name('route-assignments.update');
    Route::delete('/route-assignments/{routeAssignment}', [RouteAssignmentController::class, 'destroy'])->name('route-assignments.destroy');
    Route::get('/routes/{route}/schedules', [RouteScheduleController::class, 'index'])->name('routes.schedules.index');
    Route::get('/routes/{route}/schedules/create', [RouteScheduleController::class, 'create'])->name('routes.schedules.create');
    Route::post('/routes/{route}/schedules', [RouteScheduleController::class, 'store'])->name('routes.schedules.store');
    Route::put('/route-schedules/{routeSchedule}', [RouteScheduleController::class, 'update'])->name('route-schedules.update');
    Route::delete('/route-schedules/{routeSchedule}', [RouteScheduleController::class, 'destroy'])->name('route-schedules.destroy');
    Route::put('/route-stops/{route_stop}', [RouteStopController::class, 'update'])->name('route-stops.update');
    Route::patch('/route-stops/{route_stop}', [RouteStopController::class, 'update']);
    Route::delete('/route-stops/{route_stop}', [RouteStopController::class, 'destroy'])->name('route-stops.destroy');
    Route::resource('routes', RouteController::class)->except(['show']);

    Route::post('/vehicles/export', [VehicleController::class, 'export'])
        ->name('vehicles.export');
    Route::post('/vehicles/{vehicle}/change-status', [VehicleController::class, 'changeStatus'])
        ->name('vehicles.change-status');
    Route::get('/vehicles/{vehicle}/download-pdf', [VehicleController::class, 'downloadPdf'])
        ->name('vehicles.download-pdf');
    Route::get('/vehicles/{vehicle}/qr-code', [VehicleController::class, 'qrCode'])
        ->name('vehicles.qr-code');
    Route::get('/vehicles/{vehicle}/documents', [VehicleDocumentController::class, 'index'])
        ->name('vehicles.documents.index');
    Route::post('/vehicles/{vehicle}/documents', [VehicleDocumentController::class, 'store'])
        ->name('vehicles.documents.store');
    Route::get('/vehicle-documents/{vehicleDocument}/download', [VehicleDocumentController::class, 'download'])
        ->name('vehicle-documents.download');
    Route::get('/vehicle-documents/{vehicleDocument}/preview', [VehicleDocumentController::class, 'preview'])
        ->name('vehicle-documents.preview');
    Route::delete('/vehicle-documents/{vehicleDocument}', [VehicleDocumentController::class, 'destroy'])
        ->name('vehicle-documents.destroy');
    Route::get('/vehicles/{subject}/depot-assignments', [DepotAssignmentController::class, 'index'])
        ->defaults('module', 'vehicle')
        ->name('vehicles.depot-assignments.index');
    Route::post('/vehicles/{subject}/depot-assignments', [DepotAssignmentController::class, 'store'])
        ->defaults('module', 'vehicle')
        ->name('vehicles.depot-assignments.store');
    Route::get('/vehicles/{vehicle}/assignments', [VehicleAssignmentController::class, 'index'])
        ->name('vehicles.assignments.index');
    Route::post('/vehicles/{vehicle}/assignments', [VehicleAssignmentController::class, 'store'])
        ->name('vehicles.assignments.store');
    Route::put('/vehicle-assignments/{vehicleAssignment}', [VehicleAssignmentController::class, 'update'])
        ->name('vehicle-assignments.update');
    Route::delete('/vehicle-assignments/{vehicleAssignment}', [VehicleAssignmentController::class, 'destroy'])
        ->name('vehicle-assignments.destroy');
    Route::get('/vehicles/{vehicle}/maintenance-logs', [VehicleMaintenanceLogController::class, 'index'])
        ->name('vehicles.maintenance-logs.index');
    Route::post('/vehicles/{vehicle}/maintenance-logs', [VehicleMaintenanceLogController::class, 'store'])
        ->name('vehicles.maintenance-logs.store');
    Route::put('/vehicle-maintenance-logs/{vehicleMaintenanceLog}', [VehicleMaintenanceLogController::class, 'update'])
        ->name('vehicle-maintenance-logs.update');
    Route::delete('/vehicle-maintenance-logs/{vehicleMaintenanceLog}', [VehicleMaintenanceLogController::class, 'destroy'])
        ->name('vehicle-maintenance-logs.destroy');
    Route::get('/vehicles/{vehicle}/fuel-logs', [VehicleFuelLogController::class, 'index'])
        ->name('vehicles.fuel-logs.index');
    Route::post('/vehicles/{vehicle}/fuel-logs', [VehicleFuelLogController::class, 'store'])
        ->name('vehicles.fuel-logs.store');
    Route::put('/vehicle-fuel-logs/{vehicleFuelLog}', [VehicleFuelLogController::class, 'update'])
        ->name('vehicle-fuel-logs.update');
    Route::delete('/vehicle-fuel-logs/{vehicleFuelLog}', [VehicleFuelLogController::class, 'destroy'])
        ->name('vehicle-fuel-logs.destroy');
    Route::get('/bulk-import/{module}', [BulkImportController::class, 'form'])->name('bulk-import.form');
    Route::post('/bulk-import/{module}', [BulkImportController::class, 'import'])->name('bulk-import.store');
    Route::get('/bulk-import/{module}/sample', [BulkImportController::class, 'sample'])->name('bulk-import.sample');
    Route::resource('vehicles', VehicleController::class);

    Route::get('/financial-year-settings', [FinancialYearSettingController::class, 'index'])
        ->name('financial-year-settings.index');
    Route::put('/financial-year-settings', [FinancialYearSettingController::class, 'update'])
        ->name('financial-year-settings.update');
    Route::get('/free-no-settings', [FinancialYearSettingController::class, 'freeNo'])
        ->name('free-no-settings.index');
    Route::put('/free-no-settings', [FinancialYearSettingController::class, 'updateFreeNo'])
        ->name('free-no-settings.update');

    Route::get('/user-logs', [UserLogController::class, 'index'])
        ->name('user-logs.index');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');
    Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])
        ->name('activity-logs.export');

    Route::post('/trips/status', [TripController::class, 'status'])->name('trips.status');
    Route::post('/trips/export', [TripController::class, 'export'])
        ->name('trips.export');
    Route::get('/completed-trips', [TripController::class, 'completedTrips'])
        ->name('trips.completed.index');
    Route::get('/completed-trips/export', [TripController::class, 'completedTripsExport'])
        ->name('trips.completed.export');
    Route::get('/trip-report', [TripController::class, 'tripReport'])
        ->name('trips.report.index');
    Route::get('/trip-report/download', [TripController::class, 'downloadTripReport'])
        ->name('trips.report.download');
    Route::get('/reports/dor', [DorReportController::class, 'index'])
        ->name('reports.dor.index');
    Route::get('/reports/dor/export', [DorReportController::class, 'export'])
        ->name('reports.dor.export');
    Route::get('/reports/license-expiry', [LicenseExpiryReportController::class, 'index'])
        ->name('reports.license-expiry.index');
    Route::get('/reports/license-expiry/export', [LicenseExpiryReportController::class, 'export'])
        ->name('reports.license-expiry.export');
    Route::get('/completed-trips/{tripSheetEntry}', [TripController::class, 'completedTripView'])
        ->name('trips.completed.view');
    Route::get('/completed-trips/{tripSheetEntry}/download-pdf', [TripController::class, 'completedTripPdf'])
        ->name('trips.completed.download-pdf');
    Route::get('/trips/{trip}/assignments', [TripAssignmentController::class, 'index'])
        ->name('trips.assignments.index');
    Route::post('/trips/{trip}/assignments', [TripAssignmentController::class, 'store'])
        ->name('trips.assignments.store');
    Route::put('/trip-assignments/{tripAssignment}', [TripAssignmentController::class, 'update'])
        ->name('trip-assignments.update');
    Route::delete('/trip-assignments/{tripAssignment}', [TripAssignmentController::class, 'destroy'])
        ->name('trip-assignments.destroy');
    Route::get('/trips/{trip}/sheet-view', [TripController::class, 'sheetView'])
        ->name('trips.sheet.view');
    Route::get('/trips/{trip}/sheet/import', [TripController::class, 'importSheetForm'])
        ->name('trips.sheet.import.form');
    Route::post('/trips/{trip}/sheet/import', [TripController::class, 'importSheet'])
        ->name('trips.sheet.import');
    Route::get('/trips/{trip}/sheet/sample-excel', [TripController::class, 'sampleSheetExcel'])
        ->name('trips.sheet.sample-excel');
    Route::get('/trips/{trip}/sheet', [TripController::class, 'sheet'])
        ->name('trips.sheet');
    Route::get('/trips/{trip}/sheet/create', [TripController::class, 'createSheetEntry'])
        ->name('trips.sheet.entries.create');
    Route::post('/trips/{trip}/sheet', [TripController::class, 'storeSheet'])
        ->name('trips.sheet.store');
    Route::get('/trips/{trip}/sheet-entries/{tripSheetEntry}/edit', [TripController::class, 'editSheetEntry'])
        ->name('trips.sheet.entries.edit');
    Route::get('/trips/{trip}/sheet-entries/{tripSheetEntry}/duplicate', [TripController::class, 'duplicateSheetEntry'])
        ->name('trips.sheet.entries.duplicate');
    Route::get('/trips/{trip}/sheet-entries/{tripSheetEntry}/dor', [TripController::class, 'dorForm'])
        ->name('trips.sheet.entries.dor');
    Route::post('/trips/{trip}/sheet-entries/{tripSheetEntry}/dor', [TripController::class, 'storeDor'])
        ->name('trips.sheet.entries.dor.store');
    Route::get('/trips/{trip}/sheet-entries/{tripSheetEntry}/dor/preview', [TripController::class, 'dorPreview'])
        ->name('trips.sheet.entries.dor.preview');
    Route::delete('/trips/{trip}/sheet-entries/{tripSheetEntry}', [TripController::class, 'destroySheetEntry'])
        ->name('trips.sheet.entries.destroy');
    Route::resource('trips', TripController::class)->except(['show']);

    Route::post('/rosters/status', [RosterController::class, 'status'])->name('rosters.status');
    Route::post('/rosters/attendance', [RosterController::class, 'attendance'])->name('rosters.attendance');
    Route::post('/rosters/export', [RosterController::class, 'export'])->name('rosters.export');
    Route::get('/rosters/trip-entries', [RosterController::class, 'tripEntries'])->name('rosters.trip-entries');
    Route::get('/rosters/trip-entries/{tripSheetEntry}', [RosterController::class, 'tripEntryDetails'])->name('rosters.trip-entry-details');
    Route::get('/rosters/availability', [RosterController::class, 'availability'])->name('rosters.availability');
    Route::get('/rosters/{roster}/availability', [RosterController::class, 'availability'])->name('rosters.availability.roster');
    Route::get('/rosters/{roster}/download-pdf', [RosterController::class, 'downloadPdf'])->name('rosters.download-pdf');
    Route::post('/rosters/{roster}/reassign-driver', [RosterController::class, 'reassignDriver'])->name('rosters.reassign-driver');
    Route::post('/rosters/{roster}/reassign-vehicle', [RosterController::class, 'reassignVehicle'])->name('rosters.reassign-vehicle');
    Route::resource('rosters', RosterController::class);

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
    Route::get('/oems/{oem}/trip-sheets', [OemController::class, 'tripSheets'])
        ->name('oems.trip-sheets');
    Route::get('/oems/{oem}/trip-sheets/export', [OemController::class, 'tripSheetsExport'])
        ->name('oems.trip-sheets.export');
    Route::get('/oems/{oem}/depots', [OemDepotController::class, 'index'])
        ->name('oems.depots.index');
    Route::post('/oems/{oem}/depots', [OemDepotController::class, 'store'])
        ->name('oems.depots.store');
    Route::get('/oem-depots/depots/{depot}/branches', [OemDepotController::class, 'branchesByDepot'])
        ->name('oem-depots.branches-by-depot');
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
    Route::put('/oem-depots/{oemDepot}', [OemDepotController::class, 'update'])
        ->name('oem-depots.update');
    Route::post('/oem-depots/{oemDepot}/status', [OemDepotController::class, 'status'])
        ->name('oem-depots.status');
    Route::delete('/oem-depots/{oemDepot}', [OemDepotController::class, 'destroy'])
        ->name('oem-depots.destroy');
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

    Route::post('/salary-components/export', [SalaryComponentController::class, 'export'])
        ->name('salary-components.export');
    Route::resource('salary-components', SalaryComponentController::class)->except(['show']);
    Route::get('/salary-templates/components', [SalaryTemplateController::class, 'components'])
        ->name('salary-templates.components');
    Route::resource('salary-templates', SalaryTemplateController::class)->except(['show']);
    Route::resource('hr-letter-templates', HrLetterTemplateController::class);
    Route::get('/users/{user}/hr-letters', [HrLetterController::class, 'index'])->name('hr-letters.index');
    Route::get('/hr-letters/generate/{user}', [HrLetterController::class, 'create'])->name('hr-letters.create');
    Route::post('/hr-letters/generate/{user}', [HrLetterController::class, 'store'])->name('hr-letters.store');
    Route::get('/hr-letters/{hrLetter}', [HrLetterController::class, 'show'])->name('hr-letters.show');
    Route::get('/hr-letters/{hrLetter}/pdf', [HrLetterController::class, 'pdf'])->name('hr-letters.pdf');
    Route::get('/salary-processing/users', [SalaryProcessingController::class, 'users'])
        ->name('salary-processing.users');
    Route::post('/salary-processing/{salaryProcessing}/approve', [SalaryProcessingController::class, 'approve'])
        ->name('salary-processing.approve');
    Route::resource('salary-processing', SalaryProcessingController::class)->except(['show']);
    Route::get('/salary-reports', [SalaryReportController::class, 'index'])->name('salary-reports.index');
    Route::get('/salary-reports/export', [SalaryReportController::class, 'export'])->name('salary-reports.export');
    Route::get('/salary-reports/pdf', [SalaryReportController::class, 'pdf'])->name('salary-reports.pdf');
    Route::post('/salary-reports/send-mail', [SalaryReportController::class, 'sendMail'])->name('salary-reports.send-mail');
    Route::get('/salary-reports/{salaryProcessingItem}', [SalaryReportController::class, 'show'])->name('salary-reports.show');
    Route::get('/salary-archives', [SalaryArchiveController::class, 'index'])->name('salary-archives.index');
    Route::get('/salary-archives/{salaryProcessing}', [SalaryArchiveController::class, 'show'])->name('salary-archives.show');
    Route::get('/salary-files', [SalaryFilesController::class, 'index'])->name('salary-files.index');
    Route::get('/salary-files/{salaryProcessing}/excel', [SalaryFilesController::class, 'excel'])->name('salary-files.excel');
    Route::get('/salary-files/{salaryProcessing}/pdf', [SalaryFilesController::class, 'pdf'])->name('salary-files.pdf');
    Route::get('/salary-slips', [GeneratePaySlipController::class, 'index'])->name('salary-slips.index');
    Route::get('/salary-slips/users', [GeneratePaySlipController::class, 'users'])->name('salary-slips.users');
    Route::get('/salary-slips/preview', [GeneratePaySlipController::class, 'preview'])->name('salary-slips.preview');
    Route::get('/salary-slips/pdf', [GeneratePaySlipController::class, 'pdf'])->name('salary-slips.pdf');

    Route::get('/role-permissions', [RolePermissionController::class, 'index'])
        ->name('role-permissions.index');
    Route::get('/role-permissions/{role}/permissions', [RolePermissionController::class, 'edit'])
        ->name('role-permissions.edit');
    Route::put('/role-permissions/{role}/permissions', [RolePermissionController::class, 'update'])
        ->name('role-permissions.update');

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

    Route::post('/leaves/export', [LeaveController::class, 'export'])->name('leaves.export');
    Route::post('/leaves/{leave}/change-status', [LeaveController::class, 'changeStatus'])->name('leaves.change-status');
    Route::get('/leaves/balances', [LeaveController::class, 'balances'])->name('leaves.balances');
    Route::get('/leaves/consolidated-report/data', [LeaveController::class, 'consolidatedReportData'])
        ->name('leaves.consolidated-report.data');
    Route::get('/leaves/consolidated-report/pdf', [LeaveController::class, 'downloadConsolidatedReport'])
        ->name('leaves.consolidated-report.pdf');
    Route::get('/leaves/general/create', [LeaveController::class, 'createGeneral'])->name('leaves.general.create');
    Route::get('/leaves/driver/create', [LeaveController::class, 'createDriver'])->name('leaves.driver.create');
    Route::resource('leaves', LeaveController::class)->except(['create'])->parameters(['leaves' => 'leave']);

    Route::get('/attendance-management/users-by-role', [AttendanceController::class, 'usersByRole'])
        ->name('attendance-management.users-by-role');
    Route::get('/attendance-management/import', [AttendanceController::class, 'importForm'])
        ->name('attendance-management.import.form');
    Route::post('/attendance-management/import', [AttendanceController::class, 'import'])
        ->name('attendance-management.import');
    Route::get('/attendance-management/sample-csv', [AttendanceController::class, 'sampleCsv'])
        ->name('attendance-management.sample-csv');
    Route::get('/attendance-management/{year}/{month}/manage', [AttendanceController::class, 'manage'])
        ->whereNumber(['year', 'month'])
        ->name('attendance-management.manage');
    Route::post('/attendance-management/update', [AttendanceController::class, 'update'])
        ->name('attendance-management.update');
    Route::get('/attendance-management/{year}/{month}/print', [AttendanceController::class, 'print'])
        ->whereNumber(['year', 'month'])
        ->name('attendance-management.print');
    Route::get('/attendance-management/{year}/{month}/export', [AttendanceController::class, 'export'])
        ->whereNumber(['year', 'month'])
        ->name('attendance-management.export');
    Route::get('/attendance-management/{year}/{month}/download-pdf', [AttendanceController::class, 'downloadPdf'])
        ->whereNumber(['year', 'month'])
        ->name('attendance-management.pdf');
    Route::resource('attendance-management', AttendanceController::class)
        ->only(['index', 'create', 'store']);

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
    Route::get('/staff-management/reporting-managers', [StaffManagementController::class, 'reportingManagers'])
        ->name('staff-management.reporting-managers');
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
    Route::post('/controller-management/{controller_management}/regenerate-passcode', [ControllerManagementController::class, 'regeneratePasscode'])
        ->name('controller-management.regenerate-passcode');
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
    Route::get('/controller-management/{subject}/depot-assignments', [DepotAssignmentController::class, 'index'])
        ->defaults('module', 'controller')
        ->name('controller-management.depot-assignments.index');
    Route::post('/controller-management/{subject}/depot-assignments', [DepotAssignmentController::class, 'store'])
        ->defaults('module', 'controller')
        ->name('controller-management.depot-assignments.store');
    Route::resource('controller-management', ControllerManagementController::class);

    Route::post('/supervisor-management/status', [SupervisorManagementController::class, 'status'])
        ->name('supervisor-management.status');
    Route::post('/supervisor-management/{supervisor_management}/regenerate-passcode', [SupervisorManagementController::class, 'regeneratePasscode'])
        ->name('supervisor-management.regenerate-passcode');
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
    Route::get('/supervisor-management/{subject}/depot-assignments', [DepotAssignmentController::class, 'index'])
        ->defaults('module', 'supervisor')
        ->name('supervisor-management.depot-assignments.index');
    Route::post('/supervisor-management/{subject}/depot-assignments', [DepotAssignmentController::class, 'store'])
        ->defaults('module', 'supervisor')
        ->name('supervisor-management.depot-assignments.store');
    Route::resource('supervisor-management', SupervisorManagementController::class);

    Route::get('/driver-management/districts-by-state', [DriverManagementController::class, 'districtsByState'])
        ->name('driver-management.districts-by-state');
    Route::get('/driver-management/locations-by-district', [DriverManagementController::class, 'locationsByDistrict'])
        ->name('driver-management.locations-by-district');
    Route::post('/driver-management/status', [DriverManagementController::class, 'status'])
        ->name('driver-management.status');
    Route::post('/driver-management/{driver_management}/regenerate-passcode', [DriverManagementController::class, 'regeneratePasscode'])
        ->name('driver-management.regenerate-passcode');
    Route::post('/driver-management/export', [DriverManagementController::class, 'export'])
        ->name('driver-management.export');
    Route::get('/driver-management/{driver_management}/download-pdf', [DriverManagementController::class, 'downloadPdf'])
        ->name('driver-management.download-pdf');
    Route::get('/driver-management/{driver_management}/qr-code', [DriverManagementController::class, 'qrCode'])
        ->name('driver-management.qr-code');
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
    Route::get('/driver-management/{subject}/depot-assignments', [DepotAssignmentController::class, 'index'])
        ->defaults('module', 'driver')
        ->name('driver-management.depot-assignments.index');
    Route::post('/driver-management/{subject}/depot-assignments', [DepotAssignmentController::class, 'store'])
        ->defaults('module', 'driver')
        ->name('driver-management.depot-assignments.store');
    Route::resource('driver-management', DriverManagementController::class);

    Route::get('/depot-assignments/reporting-managers', [DepotAssignmentController::class, 'reportingManagers'])
        ->name('depot-assignments.reporting-managers');
    Route::put('/depot-assignments/{depotAssignment}', [DepotAssignmentController::class, 'update'])
        ->name('depot-assignments.update');
    Route::delete('/depot-assignments/{depotAssignment}', [DepotAssignmentController::class, 'destroy'])
        ->name('depot-assignments.destroy');

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

    return 'Storage link created successfully!';
});

Route::get('/clear-all', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');

    return 'All cache cleared!';
});

Route::get('system/migrate/{filename}', function ($filename) {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/'.$filename.'.php',
        '--force' => true,
    ]);

    return '<pre>'.Artisan::output().'</pre>';
});

Route::get('system/migrate-fresh', function () {
    Artisan::call('migrate:fresh', ['--seed' => true]);

    return 'Database migrated fresh and seeded successfully!';
})->name('system.migrate-fresh');

Route::get('system/run-seeder/{seeder}', function (string $seeder) {
    $seederClass = "Database\\Seeders\\{$seeder}";

    if (! class_exists($seederClass)) {
        return response()->json([
            'success' => false,
            'message' => "Seeder {$seeder} not found.",
        ], 404);
    }

    Artisan::call('db:seed', [
        '--class' => $seederClass,
        '--force' => true,
    ]);

    return response()->json([
        'success' => true,
        'message' => "{$seeder} executed successfully.",
        'output' => Artisan::output(),
    ]);
})->name('system.run-seeder');

Route::get('system/today-trip-notifications', function () {
    $exitCode = Artisan::call('controllers:today-trip-notifications', [
        '--force' => true,
    ]);

    $successful = $exitCode === 0;

    return response()->json([
        'success' => $successful,
        'message' => $successful
            ? 'Today trip notifications command executed successfully.'
            : 'Today trip notifications command completed with delivery failures. Check today_trip_notification_logs.error.',
        'exit_code' => $exitCode,
        'output' => Artisan::output(),
    ], $successful ? 200 : 422);
})->name('system.today-trip-notifications');

Route::get('system/driver-today-trip-notifications', function () {
    $exitCode = Artisan::call('drivers:today-trip-notifications', [
        '--force' => true,
    ]);

    $successful = $exitCode === 0;

    return response()->json([
        'success' => $successful,
        'message' => $successful
            ? 'Driver today trip notifications command executed successfully.'
            : 'Driver today trip notifications command completed with delivery failures. Check driver_trip_notification_logs.error.',
        'exit_code' => $exitCode,
        'output' => Artisan::output(),
    ], $successful ? 200 : 422);
})->name('system.driver-today-trip-notifications');

Route::get('system/driver-document-expiry-notifications', function () {
    $exitCode = Artisan::call('drivers:document-expiry-notifications', [
        '--force' => true,
    ]);

    $successful = $exitCode === 0;

    return response()->json([
        'success' => $successful,
        'message' => $successful
            ? 'Driver document expiry notifications command executed successfully.'
            : 'Driver document expiry notifications command completed with delivery failures. Check driver_document_expiry_notification_logs.error.',
        'exit_code' => $exitCode,
        'output' => Artisan::output(),
    ], $successful ? 200 : 422);
})->name('system.driver-document-expiry-notifications');

require __DIR__.'/auth.php';
