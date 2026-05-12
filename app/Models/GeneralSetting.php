<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('general_settings')]
#[Fillable([
    'financial_year'
])]
class GeneralSetting extends Model
{
    use HasFactory;
}
