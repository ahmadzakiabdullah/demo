<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'organization_id'])]
class Role extends Model
{
    public const SYSTEM_OWNER = 'system_owner';

    public const ORG_ADMIN = 'org_admin';

    public const EVENT_ORGANIZER = 'event_organizer';

    public const SPORTS_MANAGER = 'sports_manager';

    public const TEAM_MANAGER = 'team_manager';

    public const ATHLETE = 'athlete';

    public const OFFICIAL = 'official';

    public const VOLUNTEER = 'volunteer';

    public const MEDIA = 'media';

    /**
     * @return list<string>
     */
    public static function systemSlugs(): array
    {
        return [
            self::SYSTEM_OWNER,
            self::ORG_ADMIN,
            self::EVENT_ORGANIZER,
            self::SPORTS_MANAGER,
            self::TEAM_MANAGER,
            self::ATHLETE,
            self::OFFICIAL,
            self::VOLUNTEER,
            self::MEDIA,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function isSystemRole(): bool
    {
        return $this->organization_id === null;
    }
}