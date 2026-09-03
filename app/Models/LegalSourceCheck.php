<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalSourceCheck extends Model
{
    protected $fillable = [
        'legal_unit_id',
        'checked_by',
        'checked_at',
        'source_url',
        'source_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function legalUnit(): BelongsTo
    {
        return $this->belongsTo(LegalUnit::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'checked_by');
    }
}
