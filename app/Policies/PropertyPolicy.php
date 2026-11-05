<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy extends BasePropertyPolicy
{
    protected function propertyId(mixed $model): ?int
    {
        return $model instanceof Property ? $model->id : null;
    }

    /** Adding and removing sites is the account owner's call, not a team member's. */
    public function create(User $user): bool
    {
        return ! $user->isTeamMember();
    }

    public function delete(User $user, mixed $model): bool
    {
        return ! $user->isTeamMember() && parent::delete($user, $model);
    }
}
