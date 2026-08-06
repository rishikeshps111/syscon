<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HousekeepingManagementExport implements FromCollection, WithHeadings
{
    public function __construct(private $query = null) {}
    public function collection()
    {
        return ($this->query ?: User::role('Housekeeping')->with('housekeepingProfile.depot'))->get()->map(function ($user) {
            $p = $user->housekeepingProfile;
            return [$user->code, $user->name, $user->email, $user->full_phone, $p?->aadhaar_number, $p?->depot?->name, $p?->employment_type_label, $p?->joining_date?->format('d M Y'), $p?->salary, $user->is_active ? 'Active' : 'Inactive'];
        });
    }
    public function headings(): array { return ['Housekeeping Code', 'Name', 'Email', 'Phone', 'Aadhaar Number', 'Depot', 'Employment Type', 'Joining Date', 'Salary', 'Status']; }
}
