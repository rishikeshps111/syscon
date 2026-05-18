<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DriverManagementExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: User::role('Driver')->with(['driverProfile.state', 'driverProfile.district', 'driverProfile.location', 'driverProfile.depot', 'driverProfile.branchLocation']);
    }

    public function collection()
    {
        return $this->query->get()->map(function ($user) {
            $profile = $user->driverProfile;

            return [
                'Driver Code' => $user->code,
                'Name' => $user->name,
                'Email' => $user->email,
                'Phone' => $user->full_phone,
                'Alternate Phone' => trim(($profile?->alternate_country_code ?? '') . ' ' . ($profile?->alternate_phone ?? '')),
                'Aadhaar Number' => $profile?->aadhaar_number,
                'State' => $profile?->state?->name,
                'District' => $profile?->district?->name,
                'City' => $profile?->location?->name,
                'License Number' => $profile?->license_number,
                'License Type' => $profile?->license_type_label,
                'License Expiry' => $profile?->expiry_date?->format('d M Y'),
                'Employment Type' => $profile?->employment_type_label,
                'Joining Date' => $profile?->joining_date?->format('d M Y'),
                'Salary' => $profile?->salary,
                'Depot' => $profile?->depot?->name,
                'Branch' => $profile?->branchLocation?->name,
                'Police Verification' => $profile?->police_verification_status_label,
                'Verification Status' => $profile?->verification_status_label,
                'Status' => $user->is_active ? 'Active' : 'Inactive',
                'Created At' => $user->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Driver Code',
            'Name',
            'Email',
            'Phone',
            'Alternate Phone',
            'Aadhaar Number',
            'State',
            'District',
            'City',
            'License Number',
            'License Type',
            'License Expiry',
            'Employment Type',
            'Joining Date',
            'Salary',
            'Depot',
            'Branch',
            'Police Verification',
            'Verification Status',
            'Status',
            'Created At',
        ];
    }
}
