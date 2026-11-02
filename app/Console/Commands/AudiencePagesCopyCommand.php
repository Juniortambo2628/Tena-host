<?php

namespace App\Console\Commands;

/**
 * Gives /hosts and /business the homepage's new look without changing their
 * story: the drawn WiFi page in the hero, drawn mockups in the product rows,
 * the dark proof section, and the demo, table and calculator sections (added
 * hidden by migration). Existing wording stays as the admin set it; only the
 * new fields listed below are written.
 *
 * Same safety as tenafi:homepage-copy: --dry-run lists every change, a
 * backup is saved before applying, and --restore= puts it back.
 */
class AudiencePagesCopyCommand extends HomepageCopyCommand
{
    protected $signature = 'tenafi:audience-pages {--dry-run : Show the changes without saving anything} {--restore= : Restore a backup file from storage/app/cms-backups}';

    protected $description = 'Apply the new look to /hosts and /business (with --dry-run diff and a backup first)';

    private const SECTIONS = ['hero', 'demo', 'worth_calculator', 'comparison_table', 'detailed_features', 'credibility'];

    protected const TARGETS = [
        'hosts' => self::SECTIONS,
        'business' => self::SECTIONS,
    ];

    protected const PAGES = [];

    protected const ORDERED = ['hosts', 'business'];

    protected const ONLY_FIELDS = [
        'hero' => ['visual', 'visual_screen', 'visual_cards.*', 'ticks', 'show_slots', 'callback_*'],
        'detailed_features' => ['sections.*.mockup', 'sections.*.screen'],
        'credibility' => ['style', 'quote', 'quote_name', 'sources'],
    ];

    protected const DONE = '/hosts and /business already have the new look. Nothing to do.';

    protected const BACKUP_PREFIX = 'audience-pages';
}
