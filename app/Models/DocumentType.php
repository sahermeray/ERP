<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'name', 'code', 'description', 'retention_period_months', 'is_active'])]
class DocumentType extends Model
{
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
