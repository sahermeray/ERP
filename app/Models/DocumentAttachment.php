<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'uploaded_by',
    'title',
    'is_primary',
    'original_file_name',
    'storage_disk',
    'storage_path',
    'mime_type',
    'size_bytes',
])]
class DocumentAttachment extends Model
{
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
