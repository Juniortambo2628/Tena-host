<?php

namespace App\Policies;

use App\Models\User;

abstract class BasePropertyPolicy
{
    /**
     * Return the property ID used for ownership checks.
     */
    abstract protected function propertyId(mixed $model): ?int;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->userOwns($user, $model);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->userOwns($user, $model);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->userOwns($user, $model);
    }

    public function restore(User $user, mixed $model): bool
    {
        return $this->userOwns($user, $model);
    }

    public function forceDelete(User $user, mixed $model): bool
    {
        return $this->userOwns($user, $model);
    }

    protected function userOwns(User $user, mixed $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $propertyId = $this->propertyId($model);

        if ($propertyId === null) {
            return false;
        }

        return in_array($propertyId, $user->propertyIds(), true);
    }
}
