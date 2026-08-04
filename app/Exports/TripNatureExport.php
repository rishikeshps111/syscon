<?php

namespace App\Exports;

use App\Models\TripNature;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TripNatureExport implements FromCollection, WithHeadings
{
    public function __construct(private $query = null) {}

    public function collection()
    {
        $query = $this->query ?: TripNature::select('title', 'description', 'is_active', 'created_at');

        return $query->get()->map(fn ($record) => [
            'Title' => $record->title,
            'Description' => $record->description,
            'Status' => $record->is_active ? 'Active' : 'Inactive',
            'Created At' => $record->created_at->format('d M Y'),
        ]);
    }

    public function headings(): array
    {
        return ['Title', 'Description', 'Status', 'Created At'];
    }
}
