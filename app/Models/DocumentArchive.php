<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'archive_number',
    'archive_location_id',
    'archived_by',
    'archived_at',
    'retention_until',
    'disposition_status',
    'reviewed_at',
    'disposed_at',
    'disposed_by',
    'notes',
])]
class DocumentArchive extends Model
{
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'retention_until' => 'date',
            'reviewed_at' => 'datetime',
            'disposed_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
