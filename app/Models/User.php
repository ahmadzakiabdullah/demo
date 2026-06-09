<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Auditable;
use App\Support\Permissions;
use App\Models\Event;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasApiTokens, HasFactory, Notifiable;

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
        ];
    }

    public function systemRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role_id', 'status'])
            ->withTimestamps();
    }

    public function isSystemOwner(): bool
    {
        return $this->hasSystemRole(Role::SYSTEM_OWNER);
    }

    public function isAdmin(): bool
    {
        return $this->isSystemOwner();
    }

    public function hasSystemRole(string $slug): bool
    {
        return $this->systemRoles()
            ->where('slug', $slug)
            ->whereNull('organization_id')
            ->exists();
    }

    public function hasOrganizationRole(Organization $organization, string $slug): bool
    {
        $role = $this->organizationRole($organization);

        return $role?->slug === $slug;
    }

    public function hasPermission(string $permission, ?Organization $organization = null): bool
    {
        if ($this->isSystemOwner()) {
            return true;
        }

        if ($this->systemRoleHasPermission($permission)) {
            return true;
        }

        if ($organization !== null) {
            return $this->organizationRoleHasPermission($organization, $permission);
        }

        return false;
    }

    public function organizationRoleHasPermission(Organization $organization, string $permission): bool
    {
        $role = $this->organizationRole($organization);

        return $role?->permissions()->where('slug', $permission)->exists() ?? false;
    }

    public function systemRoleHasPermission(string $permission): bool
    {
        return $this->systemRoles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
            ->exists();
    }

    public function organizationRole(?Organization $organization = null): ?Role
    {
        if ($organization === null) {
            return null;
        }

        $membership = $this->organizations()
            ->where('organizations.id', $organization->id)
            ->first();

        if ($membership === null || $membership->pivot->role_id === null) {
            return null;
        }

        return Role::query()->find($membership->pivot->role_id);
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignableSystemRoles(): Collection
    {
        return Role::query()
            ->whereNull('organization_id')
            ->whereIn('slug', [Role::SYSTEM_OWNER])
            ->orderBy('name')
            ->get();
    }

    public function syncSystemRole(?string $slug): void
    {
        if ($slug === null || $slug === '') {
            $this->systemRoles()->detach();

            return;
        }

        $role = Role::query()
            ->whereNull('organization_id')
            ->where('slug', $slug)
            ->firstOrFail();

        $this->systemRoles()->sync([$role->id]);
    }

    public function primarySystemRole(): ?Role
    {
        return $this->systemRoles()
            ->whereNull('organization_id')
            ->orderBy('name')
            ->first();
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission(Permissions::slug('users', 'manage'))
            || $this->hasPermission(Permissions::slug('users', 'view'));
    }

    public function canManageOrganizations(): bool
    {
        return $this->hasPermission(Permissions::slug('organizations', 'manage'))
            || $this->hasPermission(Permissions::slug('organizations', 'create'));
    }

    public function hasPermissionInAnyOrganization(string $permission): bool
    {
        return $this->organizations()
            ->get()
            ->contains(fn (Organization $organization) => $this->organizationRoleHasPermission($organization, $permission));
    }

    public function canViewAuditLogs(): bool
    {
        return $this->hasPermission(Permissions::slug('audit_logs', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('audit_logs', 'view'));
    }

    public function canViewEvents(): bool
    {
        return $this->hasPermission(Permissions::slug('events', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('events', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewSports(): bool
    {
        return $this->hasPermission(Permissions::slug('sports', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('sports', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewAthletes(): bool
    {
        return $this->hasPermission(Permissions::slug('athletes', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('athletes', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewTeams(): bool
    {
        return $this->hasPermission(Permissions::slug('teams', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('teams', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewOfficials(): bool
    {
        return $this->hasPermission(Permissions::slug('officials', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('officials', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewVenues(): bool
    {
        return $this->hasPermission(Permissions::slug('venues', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('venues', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewCompetitions(): bool
    {
        return $this->hasPermission(Permissions::slug('competitions', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('competitions', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewResults(): bool
    {
        return $this->hasPermission(Permissions::slug('results', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('results', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewEventParticipants(): bool
    {
        return $this->hasPermission(Permissions::slug('event_participants', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('event_participants', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function canViewParticipantSportEntries(): bool
    {
        return $this->hasPermission(Permissions::slug('participant_sport_entries', 'view'))
            || $this->hasPermissionInAnyOrganization(Permissions::slug('participant_sport_entries', 'view'))
            || $this->assignedEvents()->exists();
    }

    public function assignedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function isAssignedToEvent(Event $event, ?array $roles = null): bool
    {
        $query = $this->assignedEvents()->where('events.id', $event->id);

        if ($roles !== null) {
            $query->wherePivotIn('role', $roles);
        }

        return $query->exists();
    }

    public function resolveAuditOrganizationId(): ?int
    {
        $organization = request()->attributes->get('currentOrganization');

        if ($organization instanceof Organization) {
            return $organization->id;
        }

        if (request()->hasSession() && request()->session()->has('current_organization_id')) {
            return (int) request()->session()->get('current_organization_id');
        }

        return null;
    }
}