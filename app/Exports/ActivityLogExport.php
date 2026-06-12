<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\Activitylog\Models\Activity;

class ActivityLogExport implements FromCollection, WithHeadings
{
    public function __construct(private Builder $query)
    {
    }

    public function collection()
    {
        return $this->query->get()->map(function (Activity $activity) {
            $user = $activity->causer;

            return [
                'Module' => $activity->getExtraProperty('module', '-'),
                'Event' => ucfirst((string) ($activity->event ?? $activity->description)),
                'Name of User' => $user ? trim(($user->code ? $user->code . ' - ' : '') . ($user->name ?? '-')) : '-',
                'Role' => $user?->roles?->pluck('name')->join(', ') ?: '-',
                'Date and Time' => $activity->created_at?->format('d M Y h:i A') ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Module',
            'Event',
            'Name of User',
            'Role',
            'Date and Time',
        ];
    }
}
