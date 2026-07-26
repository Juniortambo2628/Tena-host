<?php

namespace App\Policies;

use App\Models\AccessPoint;

class AccessPointPolicy extends BasePropertyPolicy
{
    protected function propertyId(mixed $model): ?int
    {
        return $model instanceof AccessPoint ? $model->property_id : null;
    }
}
