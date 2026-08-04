<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\BranchLocation;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\ControllerDocument;
use App\Models\ControllerProfile;
use App\Models\Department;
use App\Models\Depot;
use App\Models\DepotAssignment;
use App\Models\Designation;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use App\Models\GeneralSetting;
use App\Models\Holiday;
use App\Models\HrmsDocumentType;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Level;
use App\Models\Location;
use App\Models\Oem;
use App\Models\OemAddress;
use App\Models\OemBankDetail;
use App\Models\OemContact;
use App\Models\OemDocument;
use App\Models\OemStateMapping;
use App\Models\OemType;
use App\Models\Prefix;
use App\Models\Roster;
use App\Models\Route as RouteModel;
use App\Models\RouteAssignment;
use App\Models\RouteSchedule;
use App\Models\RouteStop;
use App\Models\ServiceType;
use App\Models\ShiftSetting;
use App\Models\StaffDocument;
use App\Models\StaffProfile;
use App\Models\State;
use App\Models\SupervisorDocument;
use App\Models\SupervisorProfile;
use App\Models\Trip;
use App\Models\TripNature;
use App\Models\TripAssignment;
use App\Models\TripSheet;
use App\Models\TripSheetEntry;
use App\Models\TripSheetEntryDor;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleClassification;
use App\Models\VehicleDocument;
use App\Models\VehicleFuelLog;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Database\Eloquent\Model;

class CrudActivityLogger
{
    /**
     * @var array<class-string<Model>, string>
     */
    private static array $modules = [
        Prefix::class => 'Prefix',
        State::class => 'State',
        District::class => 'District',
        Location::class => 'Location',
        ServiceType::class => 'Service Type',
        VehicleClassification::class => 'Vehicle Classification',
        TripNature::class => 'Trip Nature',
        DocumentType::class => 'Document Type',
        ComplaintCategory::class => 'Complaint Category',
        Depot::class => 'Depot',
        DepotAssignment::class => 'Depot Assignment',
        RouteModel::class => 'Route',
        RouteStop::class => 'Route Stop',
        RouteAssignment::class => 'Route Assignment',
        RouteSchedule::class => 'Route Schedule',
        Vehicle::class => 'Vehicle Management',
        VehicleAssignment::class => 'Vehicle Assignment',
        VehicleDocument::class => 'Vehicle Document',
        VehicleFuelLog::class => 'Vehicle Fuel Log',
        VehicleMaintenanceLog::class => 'Vehicle Maintenance Log',
        Trip::class => 'Trip',
        TripAssignment::class => 'Trip Assignment',
        TripSheet::class => 'Trip Sheet',
        TripSheetEntry::class => 'Trip Sheet Entry',
        TripSheetEntryDor::class => 'Trip Sheet DOR',
        Roster::class => 'Roaster',
        OemType::class => 'OEM Type',
        Oem::class => 'OEM',
        OemAddress::class => 'OEM Address',
        OemBankDetail::class => 'OEM Bank Detail',
        OemContact::class => 'OEM Contact',
        OemDocument::class => 'OEM Document',
        OemStateMapping::class => 'OEM State Mapping',
        BranchLocation::class => 'Branch Location',
        Department::class => 'Department',
        Level::class => 'Level',
        Designation::class => 'Designation',
        HrmsDocumentType::class => 'HRMS Document Type',
        LeaveType::class => 'Leave Type',
        ShiftSetting::class => 'Shift Setting',
        Leave::class => 'Leave Management',
        Attendance::class => 'Attendance Management',
        Holiday::class => 'Holiday',
        User::class => 'User',
        StaffProfile::class => 'Staff Management',
        StaffDocument::class => 'Staff Document',
        DriverProfile::class => 'Driver Management',
        DriverDocument::class => 'Driver Document',
        ControllerProfile::class => 'Controller Management',
        ControllerDocument::class => 'Controller Document',
        SupervisorProfile::class => 'Supervisor Management',
        SupervisorDocument::class => 'Supervisor Document',
        Complaint::class => 'Complaints',
        GeneralSetting::class => 'Settings',
    ];

    public static function register(): void
    {
        foreach (array_keys(self::$modules) as $model) {
            $model::created(fn (Model $record) => self::log($record, 'created'));
            $model::updated(fn (Model $record) => self::log($record, 'updated'));
            $model::deleted(fn (Model $record) => self::log($record, 'deleted'));
        }
    }

    private static function log(Model $record, string $event): void
    {
        if (! auth()->check()) {
            return;
        }

        $module = self::$modules[$record::class] ?? class_basename($record);
        $attributes = $event === 'deleted' ? $record->getOriginal() : $record->getAttributes();
        $old = $event === 'updated' ? $record->getOriginal() : [];
        $name = self::recordName($record, $attributes);

        activity('crud')
            ->event($event)
            ->causedBy(auth()->user())
            ->performedOn($record)
            ->withProperties([
                'module' => $module,
                'record_name' => $name,
                'status' => self::statusValue($attributes),
                'attributes' => $attributes,
                'old' => $old,
            ])
            ->log($module . ' ' . $event);
    }

    private static function recordName(Model $record, array $attributes): string
    {
        foreach (['name', 'title', 'trip_title', 'code', 'vehicle_no', 'email'] as $field) {
            if (! empty($attributes[$field])) {
                return (string) $attributes[$field];
            }
        }

        return class_basename($record) . ' #' . $record->getKey();
    }

    private static function statusValue(array $attributes): ?string
    {
        foreach (['status', 'is_active', 'attendance_status'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $value = $attributes[$field];

                if (is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1') {
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Active' : 'Inactive';
                }

                return $value === null ? null : ucfirst(str_replace('_', ' ', (string) $value));
            }
        }

        return null;
    }
}
