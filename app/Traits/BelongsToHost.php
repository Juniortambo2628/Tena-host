<?php

namespace App\Traits;

use App\Models\User;

trait BelongsToHost
{
    /**
     * Scope queries to models associated with the given host's properties.
     */
    public function scopeForHost($query, User $user)
    {
        $column = property_exists($this, 'hostScopeColumn')
            ? $this->hostScopeColumn
            : 'property_id';

        $table = $this->getTable();

        if ($column === 'user_id') {
            // A team member sees the owner's sites they were given, not sites of their own.
            return $user->isTeamMember()
                ? $query->whereIn("{$table}.id", $user->propertyIds())
                : $query->where("{$table}.{$column}", $user->id);
        }

        return $query->whereIn("{$table}.{$column}", $user->propertyIds());
    }
}
