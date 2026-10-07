<?php

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\Setting;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_to_tenafi(): void
    {
        $this->assertSame('TenaFi', Brand::name());
        $this->assertSame('/brand/tenafi-logo.svg', Brand::logoUrl());
        $this->assertStringEndsWith('/brand/tenafi-logo.png', Brand::emailLogoUrl());

        $this->get('/login')->assertInertia(fn ($page) => $page->where('brand.name', 'TenaFi'));
        $this->assertSame("You've been invited to join TenaFi", (new UserInvitationMail(name: 'A'))->envelope()->subject);
    }

    public function test_site_name_setting_renames_everywhere(): void
    {
        Setting::setValue('site_name', 'Tena Kenya', 'general', 'string');

        $this->get('/login')->assertInertia(fn ($page) => $page->where('brand.name', 'Tena Kenya'));
        $this->get('/portal?id=aa:bb:cc:dd:ee:ff')->assertSee('Powered by Tena Kenya');
    }
}
