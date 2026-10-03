<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'is_system'])]
class Role extends Model
{
    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationMembership::class, 'organization_user_role', 'role_id', 'organization_user_id')
            ->withPivot(['assigned_by', 'assigned_at']);
    }
}
