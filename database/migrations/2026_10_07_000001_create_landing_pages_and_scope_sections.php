<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-page CMS: every landing section now belongs to a page (/, /hosts,
 * /business, plus a non-routable "site" page for header/footer/plans that
 * every page shares). Existing sections were the old single-page homepage,
 * which the TenaFi relaunch moves to /hosts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // home, hosts, business, site
            $table->string('name');
            $table->boolean('is_routable')->default(true); // false for the shared "site" page
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('landing_sections', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable()->after('id')->constrained('landing_pages')->cascadeOnDelete();
        });

        $now = now();
        $pages = [
            ['slug' => 'home', 'name' => 'Main landing page', 'is_routable' => true, 'sort_order' => 0],
            ['slug' => 'hosts', 'name' => 'Short-term rental operators', 'is_routable' => true, 'sort_order' => 1],
            ['slug' => 'business', 'name' => 'Business owners', 'is_routable' => true, 'sort_order' => 2],
            ['slug' => 'site', 'name' => 'Site-wide (header, footer, plans)', 'is_routable' => false, 'sort_order' => 3],
        ];
        foreach ($pages as $page) {
            DB::table('landing_pages')->insert($page + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        $hostsId = DB::table('landing_pages')->where('slug', 'hosts')->value('id');
        DB::table('landing_sections')->update(['page_id' => $hostsId]);

        Schema::table('landing_sections', function (Blueprint $table) {
            $table->dropUnique(['section_key']);
            $table->unique(['page_id', 'section_key']);
        });

        // Sign-ups from both public pages land in the existing registrations
        // table (the old waitlist rows become type = host automatically).
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('type', 20)->default('host')->after('id')->index();
            $table->string('business_name', 150)->nullable()->after('last_name');
            $table->json('answers')->nullable()->after('agree_updates');
            $table->text('consent_text')->nullable()->after('answers');
            $table->timestamp('consented_at')->nullable()->after('consent_text');
            $table->string('consent_ip', 45)->nullable()->after('consented_at');
            $table->string('source_page', 50)->nullable()->after('consent_ip');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('last_name', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'business_name', 'answers', 'consent_text', 'consented_at', 'consent_ip', 'source_page']);
        });

        Schema::table('landing_sections', function (Blueprint $table) {
            $table->dropUnique(['page_id', 'section_key']);
        });

        // Only the old homepage sections can survive a rollback to the
        // single-page schema (section_key must be globally unique again).
        $hostsId = DB::table('landing_pages')->where('slug', 'hosts')->value('id');
        DB::table('landing_sections')->where('page_id', '!=', $hostsId)->delete();

        Schema::table('landing_sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('page_id');
            $table->unique('section_key');
        });

        Schema::dropIfExists('landing_pages');
    }
};
