<?php

namespace App\Models\Concerns;

use App\Scopes\OrganizationScope;

/**
 * Use this trait on models that belong to an Organization (tenant).
 * It automatically applies OrganizationScope for data isolation.
 *
 * Models using this: Event, Athlete, Team, etc.
 */
trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }
}
