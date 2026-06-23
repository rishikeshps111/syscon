<?php

namespace App\Exports;

use App\Models\StaffProfile;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffManagementExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: User::role('Staff')->with(['roles', 'staffProfile.depot', 'staffProfile.designation', 'staffProfile.location']);
    }

    public function collection()
    {
        return $this->query->get()->map(function ($user) {
            $profile = $user->staffProfile;

            return [
                'Staff Code' => $user->code,
                'Staff Name' => $user->name,
                'Email' => $user->email,
                'Phone' => $user->phone,
                'Depot' => $profile?->depot?->name,
                'Designation' => $profile?->designation?->name,
                'Category' => $profile?->category_label,
                'Employment Type' => $profile?->employment_type_label,
                'Location' => $profile?->location?->name,
                'Date of Joining' => $profile?->date_of_joining?->format('d M Y'),
                'Gross Salary' => $profile?->gross_salary,
                'Status' => $user->is_active ? 'Active' : 'Inactive',
                'Created At' => $user->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Staff Code',
            'Staff Name',
            'Email',
            'Phone',
            'Depot',
            'Designation',
            'Category',
            'Employment Type',
            'Location',
            'Date of Joining',
            'Gross Salary',
            'Status',
            'Created At',
        ];
    }
}
