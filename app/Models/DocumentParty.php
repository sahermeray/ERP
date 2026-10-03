<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'party_type',
    'organization_name',
    'contact_name',
    'email',
    'phone',
    'postal_address',
    'sort_order',
])]
class DocumentParty extends Model
{
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
