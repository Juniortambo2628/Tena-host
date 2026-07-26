<?php

namespace App\Policies;

use App\Models\Property;

class PropertyPolicy extends BasePropertyPolicy
{
    protected function propertyId(mixed $model): ?int
    {
        return $model instanceof Property ? $model->id : null;
    }
}
