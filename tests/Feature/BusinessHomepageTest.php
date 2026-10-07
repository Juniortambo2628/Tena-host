<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Amenity;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessHomepageTest extends TestCase
{
    use RefreshDatabase;

    private Property $cafe;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create(['role' => 'host', 'account_type' => 'business']);
        $this->cafe = Property::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Kahawa House',
            'offers' => "2-for-1 cappuccinos before 10am\n\nFree WiFi all day",
            'events' => 'Live music, Friday 7pm',
            'birthday_offer' => 'Happy birthday {guest_name}! Free cake at {property_name} this week.',
        ]);
        Amenity::factory()->create(['property_id' => $this->cafe->id, 'name' => 'Cappuccino', 'price' => 350, 'is_active' => true]);
        Amenity::factory()->create(['property_id' => $this->cafe->id, 'name' => 'Old special', 'is_active' => false]);
        AccessPoint::factory()->create(['property_id' => $this->cafe->id, 'mac_address' => '11:22:33:44:55:66']);
    }

    public function test_venue_page_shows_menu_offers_and_events(): void
    {
        $this->get(route('venue.show', $this->cafe))
            ->assertOk()
            ->assertSee('Kahawa House')
            ->assertSee('2-for-1 cappuccinos before 10am')
            ->assertSee('Cappuccino')->assertSee('KES 350')
            ->assertDontSee('Old special')
            ->assertSee('Live music, Friday 7pm');
    }

    public function test_rental_properties_have_no_venue_page(): void
    {
        $rental = Property::factory()->create(['user_id' => User::factory()->create(['role' => 'host'])->id]);

        $this->get(route('venue.show', $rental))->assertNotFound();
    }

    public function test_business_customers_land_on_the_venue_page_with_their_birthday_saved(): void
    {
        $this->mock(UnifiService::class, function ($mock) {
            $mock->shouldReceive('authorizeGuest')->once()->andReturnTrue();
            $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
        });

        $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertSee('name="birthday_day"', false);

        $this->post('/portal/connect', [
            'id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66', 'url' => 'http://neverssl.com',
            'first_name' => 'Brian', 'phone' => '0712345678', 'consent' => '1', 'marketing_opt_in' => '1',
            'birthday_day' => '7', 'birthday_month' => '10',
        ])->assertRedirect(route('venue.show', $this->cafe));

        $this->assertSame('10-07', Guest::sole()->birthday);
    }

    public function test_birthday_treat_goes_out_once_to_opted_in_customers(): void
    {
        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
            'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
        ]]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        $today = now('Africa/Nairobi')->format('m-d');
        Guest::factory()->create(['property_id' => $this->cafe->id, 'first_name' => 'Brian', 'phone' => '+254712345678', 'birthday' => $today, 'marketing_opt_in' => true]);
        Guest::factory()->create(['property_id' => $this->cafe->id, 'phone' => '+254700000002', 'birthday' => $today, 'marketing_opt_in' => false]);

        $this->artisan('birthdays:send')->expectsOutput('Birthday treats sent: 1');
        $this->artisan('birthdays:send')->expectsOutput('Birthday treats sent: 0');

        Http::assertSent(fn (Request $r) => $r['text']['body'] === 'Happy birthday Brian! Free cake at Kahawa House this week.');
    }
}
