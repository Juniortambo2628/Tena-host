<?php

namespace App\Policies;

use App\Models\Order;

class OrderPolicy extends BasePropertyPolicy
{
    protected function propertyId(mixed $model): ?int
    {
        return $model instanceof Order ? $model->property_id : null;
    }
}
