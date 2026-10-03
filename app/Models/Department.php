<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'parent_department_id', 'name', 'code', 'description', 'is_active'])]
class Department extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_department_id');
    }

    public function sendingDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'sending_department_id');
    }

    public function receivingDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'recipient_department_id');
    }
}
