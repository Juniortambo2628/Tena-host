<?php

namespace Tests\Feature;

use App\Models\PolicyDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenaFiPoliciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_policies_are_the_tenafi_versions(): void
    {
        foreach (PolicyDocument::all() as $policy) {
            $this->assertStringNotContainsString('Tena Host', $policy->content, $policy->slug);
            $this->assertStringNotContainsString('tena.host', $policy->content, $policy->slug);
        }

        $privacy = PolicyDocument::where('slug', 'privacy-policy')->sole();
        $this->assertStringContainsString('Reply <strong>STOP</strong>', str_replace('reply', 'Reply', $privacy->content));
        $this->assertStringContainsString('Kenya Data Protection Act', $privacy->content);
    }

    public function test_migration_leaves_admin_edited_policies_alone(): void
    {
        PolicyDocument::where('slug', 'refund-policy')->update(['content' => '<p>Our own terms</p>', 'last_reviewed_by' => 'Glen']);
        PolicyDocument::where('slug', 'terms-of-service')->update(['content' => '<p>Old Tena Host terms</p>', 'last_reviewed_by' => 'Glen']);

        (require database_path('migrations/2026_10_08_000002_tenafi_policy_documents.php'))->up();

        $this->assertSame('<p>Our own terms</p>', PolicyDocument::where('slug', 'refund-policy')->value('content'));
        $this->assertStringContainsString('TenaFi', PolicyDocument::where('slug', 'terms-of-service')->value('content'));
    }

    public function test_privacy_page_renders(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Kenya Data Protection Act');
    }
}
