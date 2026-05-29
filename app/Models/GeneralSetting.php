<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('general_settings')]
#[Fillable([
    'financial_year',
    'financial_year_from_month',
    'financial_year_to_year',
    'financial_year_to_month',
])]
class GeneralSetting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'financial_year_from_month' => 'integer',
            'financial_year_to_year' => 'integer',
            'financial_year_to_month' => 'integer',
        ];
    }
}
