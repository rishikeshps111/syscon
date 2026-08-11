<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffManagementExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: User::role(['Staff', 'Housekeeping', 'Controller', 'Supervisor'])->with([
            'roles', 'staffProfile.depot', 'staffProfile.designation', 'staffProfile.state', 'staffProfile.district', 'staffProfile.location',
            'housekeepingProfile.depot', 'housekeepingProfile.state', 'housekeepingProfile.district', 'housekeepingProfile.location',
            'controllerProfile.depot', 'controllerProfile.state', 'controllerProfile.district', 'controllerProfile.location',
            'supervisorProfile.depot', 'supervisorProfile.state', 'supervisorProfile.district', 'supervisorProfile.location',
        ]);
    }

    public function collection()
    {
        return $this->query->get()->values()->map(function ($user, int $index) {
            $role = collect(['Staff', 'Housekeeping', 'Controller', 'Supervisor'])
                ->first(fn (string $role) => $user->hasRole($role)) ?: 'Staff';
            $profile = match ($role) {
                'Housekeeping' => $user->housekeepingProfile,
                'Controller' => $user->controllerProfile,
                'Supervisor' => $user->supervisorProfile,
                default => $user->staffProfile,
            };

            return [
                'SL No' => $index + 1,
                'Code' => $user->code,
                'Ref Code' => $user->ref_code,
                'Name' => $user->name,
                'Email' => $user->email,
                'Phone' => trim(($user->country_code ?? '') . ' ' . ($user->phone ?? '')),
                'Role' => $role,
                'Designation' => $role === 'Staff' ? $profile?->designation?->name : null,
                'Depot' => $profile?->depot?->name,
                'Employment Type' => $profile?->employment_type_label,
                'Status' => $user->is_active ? 'Active' : 'Inactive',
                'Father Name' => $profile?->father_name,
                'Date of Birth' => $profile?->date_of_birth?->format('Y-m-d'),
                'Aadhaar Number' => $profile?->aadhaar_number,
                'PAN Number' => $profile?->pan_number,
                'Date of Joining' => ($role === 'Housekeeping' ? $profile?->joining_date : $profile?->date_of_joining)?->format('Y-m-d'),
                'UAN' => $profile?->uan,
                'ESIC / WC' => $profile?->esic_wc,
                'Country' => $profile?->country,
                'State' => $profile?->state?->name,
                'District' => $profile?->district?->name,
                'Location' => $profile?->location?->name,
                'Account Number' => $role === 'Housekeeping' ? $profile?->account_number : $profile?->bank_account_number,
                'IFSC Code' => $profile?->ifsc_code,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SL No',
            'Code',
            'Ref Code',
            'Name',
            'Email',
            'Phone',
            'Role',
            'Designation',
            'Depot',
            'Employment Type',
            'Status',
            'Father Name',
            'Date of Birth',
            'Aadhaar Number',
            'PAN Number',
            'Date of Joining',
            'UAN',
            'ESIC / WC',
            'Country',
            'State',
            'District',
            'Location',
            'Account Number',
            'IFSC Code',
        ];
    }
}
