<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['letter_number', 'template_id', 'user_id', 'entity_type', 'language', 'subject', 'content', 'header_logo', 'header_address', 'footer_content', 'additional_data', 'generated_by', 'generated_at'])]
class GeneratedHrLetter extends Model
{
    protected function casts(): array
    {
        return ['additional_data' => 'array', 'generated_at' => 'datetime'];
    }

    public function template(): BelongsTo { return $this->belongsTo(HrLetterTemplate::class, 'template_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
