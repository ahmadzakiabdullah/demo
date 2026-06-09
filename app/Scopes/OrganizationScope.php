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
        $organization = Request::attributes->get('currentOrganization')
            ?? (Request::hasSession() ? Request::session()->get('current_organization_id') : null);

        if ($organization && ! (auth()->check() && auth()->user()->isSystemOwner())) {
            $column = $model->getTable() . '.organization_id';

            $builder->where($column, is_object($organization) ? $organization->id : $organization);
        }
    }
}
