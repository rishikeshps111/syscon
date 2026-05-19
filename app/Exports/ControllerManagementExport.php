<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ControllerManagementExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: User::role('Controller')->with(['roles', 'controllerProfile.depot', 'controllerProfile.location']);
    }

    public function collection()
    {
        return $this->query->get()->map(function ($user) {
            $profile = $user->controllerProfile;

            return [
                'Controller Code' => $user->code,
                'Controller Name' => $user->name,
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
            'Controller Code',
            'Controller Name',
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
