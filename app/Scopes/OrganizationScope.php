<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Request;

/**
 * Global scope to automatically filter tenant models by the current organization.
 *
 * Applied to models that belong to an organization (Event, Athlete, Team, etc.).
 * Respects system owners (no filter) and falls back gracefully.
 *
 * See POLISH-05 in DEVELOPMENT.md for full hardening plan.
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply tenant filter if currentOrganization attribute is explicitly set
        // (by SetCurrentOrganization middleware). This prevents unintended filtering
        // in tests or system contexts. System owners bypass anyway.
        $req = request();
        $organization = $req->attributes->get('currentOrganization');

        if ($organization && ! (auth()->check() && auth()->user()->isSystemOwner())) {
            $column = $model->getTable() . '.organization_id';

            $builder->where($column, is_object($organization) ? $organization->id : $organization);
        }
    }
}
