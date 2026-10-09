<?php

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_to_tenafi(): void
    {
        $this->assertSame('TenaFi', Brand::name());
        $this->assertStringEndsWith('/brand/tenafi-logo.png', Brand::emailLogoUrl());

        $this->get('/login')->assertInertia(fn ($page) => $page
            ->where('brand.name', 'TenaFi')
            ->where('brand.header', '/brand/tenafi-logo-header.png')
            ->where('brand.footer', '/brand/tenafi-logo-footer.png')
            ->where('brand.favicon', '/brand/tenafi-favicon-512.png'));
        $this->get('/login')->assertSee('/brand/tenafi-favicon-512.png', false);
        foreach (Brand::LOGOS as [, $default]) {
            $this->assertFileExists(public_path(ltrim($default, '/')));
        }
        $this->assertSame("You've been invited to join TenaFi", (new UserInvitationMail(name: 'A'))->envelope()->subject);
    }

    public function test_admin_uploads_a_logo_that_shows_everywhere_and_can_reset_it(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.branding.upload', 'logo_footer_url'), ['file' => UploadedFile::fake()->image('footer.png', 400, 100)])
            ->assertRedirect();

        $url = Setting::getValue('logo_footer_url');
        $this->assertStringContainsString('/storage/branding/', $url);
        $this->get(route('admin.settings.index'))->assertInertia(fn ($page) => $page
            ->where('brand.footer', $url)
            ->where('logos.2.key', 'logo_footer_url')
            ->where('logos.2.custom', true));

        $this->actingAs($admin)->post(route('admin.branding.upload', 'favicon_url'), ['file' => UploadedFile::fake()->image('icon.png', 64, 64)]);
        $this->get('/portal?id=aa:bb:cc:dd:ee:ff')->assertSee(Setting::getValue('favicon_url'), false);

        // Emails need a raster official logo.
        $this->actingAs($admin)
            ->post(route('admin.branding.upload', 'logo_url'), ['file' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')])
            ->assertSessionHasErrors('file');

        $this->actingAs($admin)->delete(route('admin.branding.reset', 'logo_footer_url'))->assertRedirect();
        $this->assertSame('/brand/tenafi-logo-footer.png', Brand::asset('logo_footer_url'));
        $this->assertSame([], Storage::disk('public')->files('branding/'.basename($url)));

        $this->actingAs($admin)->post(route('admin.branding.upload', 'site_name'))->assertNotFound();
    }

    public function test_site_name_setting_renames_everywhere(): void
    {
        Setting::setValue('site_name', 'Tena Kenya', 'general', 'string');

        $this->get('/login')->assertInertia(fn ($page) => $page->where('brand.name', 'Tena Kenya'));
        $this->get('/portal?id=aa:bb:cc:dd:ee:ff')->assertSee('Powered by Tena Kenya');
    }
}
