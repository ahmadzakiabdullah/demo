<?php

namespace App\Policies;

use App\Models\Accreditation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccreditationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // stub for org users
    }

    public function view(User $user, Accreditation $accreditation): bool
    {
        return true; // stub
    }

    public function create(User $user): bool
    {
        return true; // stub
    }

    public function update(User $user, Accreditation $accreditation): bool
    {
        return true; // stub
    }

    public function delete(User $user, Accreditation $accreditation): bool
    {
        return true; // stub
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Accreditation $accreditation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Accreditation $accreditation): bool
    {
        return false;
    }
}
