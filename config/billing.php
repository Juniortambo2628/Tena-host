<?php

/*
| TenaFi subscription pricing (KES). Matches the public pricing sections
| (Admin → Public Pages → pricing); change both together.
*/

return [
    'currency' => 'KES',

    // Price per unit (a rental unit or a business location) per month.
    'plans' => [
        'basic' => ['name' => 'Basic', 'price' => 3000],
        'starter' => ['name' => 'Starter', 'price' => 4500],
        'growth' => ['name' => 'Growth', 'price' => 6000],
    ],

    'default_plan' => 'starter',

    // Minimum units => percent off the unit price. Highest match wins.
    'volume_discounts' => [
        50 => 30,
        10 => 20,
    ],

    // Each extra device at the same site, per month.
    'extra_device_price' => 1500,

    // discount: percent off; free_months: months not charged.
    'cycles' => [
        'monthly' => ['label' => 'Monthly', 'months' => 1, 'discount' => 0, 'free_months' => 0],
        'quarterly' => ['label' => 'Quarterly', 'months' => 3, 'discount' => 5, 'free_months' => 0],
        'yearly' => ['label' => 'Yearly', 'months' => 12, 'discount' => 0, 'free_months' => 2],
    ],
];
