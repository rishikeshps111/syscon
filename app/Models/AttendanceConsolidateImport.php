<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['year', 'month', 'depot_id', 'depot_name', 'original_filename', 'file_path', 'file_checksum', 'employee_count', 'imported_by', 'imported_by_name', 'imported_at'])]
class AttendanceConsolidateImport extends Model
{
    protected function casts(): array
    {
        return ['year' => 'integer', 'month' => 'integer', 'imported_at' => 'datetime'];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(AttendanceConsolidateRow::class, 'import_id');
    }
}
