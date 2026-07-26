<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';

        config([
            'cache.default' => 'array',
            'session.driver' => 'array',
            'services.stripe.key' => '',
        ]);

        Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
        \Illuminate\Support\Facades\Cache::flush();
    }
}
