<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['department_id', 'status', 'job_title', 'joined_at'])
            ->withTimestamps();
    }

    public function activeOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('status', 'active');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function isAdministrator(): bool
    {
        $organization = $this->currentOrganization();

        if (! $organization) {
            return false;
        }

        if ($this->email === config('app.administrator_email')) {
            return true;
        }

        return $this->memberships()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))
            ->exists();
    }

    public function currentOrganization(): ?Organization
    {
        return $this->activeOrganizations()
            ->where('organizations.is_active', true)
            ->orderBy('organizations.name')
            ->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
