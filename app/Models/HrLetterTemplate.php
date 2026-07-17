<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entity_type', 'language', 'template_name', 'subject', 'content', 'header_logo', 'header_address', 'footer_content', 'is_active', 'created_by', 'updated_by'])]
class HrLetterTemplate extends Model
{
    public const ENTITY_TYPES = ['offer_letter' => 'Offer Letter', 'warning_letter' => 'Warning Letter'];

    public const LANGUAGES = [
        'English', 'Hindi', 'Assamese', 'Bengali', 'Bodo', 'Dogri', 'Gujarati', 'Kannada',
        'Kashmiri', 'Konkani', 'Maithili', 'Malayalam', 'Manipuri', 'Marathi', 'Nepali',
        'Odia', 'Punjabi', 'Sanskrit', 'Santali', 'Sindhi', 'Tamil', 'Telugu', 'Urdu',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function generatedLetters(): HasMany
    {
        return $this->hasMany(GeneratedHrLetter::class, 'template_id');
    }
}
