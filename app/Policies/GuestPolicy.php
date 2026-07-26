<?php

namespace App\Policies;

use App\Models\Guest;

class GuestPolicy extends BasePropertyPolicy
{
    protected function propertyId(mixed $model): ?int
    {
        return $model instanceof Guest ? $model->property_id : null;
    }
}
