<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'legal_name', 'description', 'address', 'email', 'phone', 'timezone', 'is_active'])]
class Organization extends Model
{
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['department_id', 'job_title', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }
}
