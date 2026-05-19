<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupervisorManagementExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: User::role('Supervisor')->with(['roles', 'supervisorProfile.depot', 'supervisorProfile.location']);
    }

    public function collection()
    {
        return $this->query->get()->map(function ($user) {
            $profile = $user->supervisorProfile;

            return [
                'Supervisor Code' => $user->code,
                'Supervisor Name' => $user->name,
                'Email' => $user->email,
                'Phone' => $user->phone,
                'Depot' => $profile?->depot?->name,
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
            'Supervisor Code',
            'Supervisor Name',
            'Email',
            'Phone',
            'Depot',
            'Employment Type',
            'Location',
            'Date of Joining',
            'Gross Salary',
            'Status',
            'Created At',
        ];
    }
}



