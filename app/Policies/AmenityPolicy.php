<?php

namespace App\Policies;

use App\Models\Amenity;

class AmenityPolicy extends BasePropertyPolicy
{
    protected function propertyId(mixed $model): ?int
    {
        return $model instanceof Amenity ? $model->property_id : null;
    }
}
