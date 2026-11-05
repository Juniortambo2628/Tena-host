<?php

namespace App\Policies;

use App\Models\Event;

/** Whoever can work on the venue can work on its events (a manager, for their sites). */
class EventPolicy extends BasePropertyPolicy
{
    protected function propertyId(mixed $model): ?int
    {
        return $model instanceof Event ? $model->property_id : null;
    }
}
