<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Seeders;

use Illuminate\Database\Seeder;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Enums\PageKind;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign;

/**
 * The kit's baseline: the first administrator in bundled sign-in (WaitlistKitAdminSeeder), one draft waitlist campaign with a starter page (hero, sign-up form, FAQ), and draft privacy
 * and terms pages, so a fresh install has something to open and edit. Runs with the application's `php artisan db:seed` (registered by the provider).
 *
 * Idempotent and safe in production: it seeds no campaign when any exists and no page that exists, holds no fake data and no
 * credential, and needs no Faker. `seeding.default = false` turns it off.
 */
final class WaitlistKitSeeder extends Seeder
{
    public const string SLUG = 'waitlist';

    public function run(KitConfig $config, CreateCampaign $createCampaign): void
    {
        // The first administrator, in bundled sign-in with seeding.admin_email set; separate from the starter content.
        $this->call(WaitlistKitAdminSeeder::class);

        if (! $config->seedsDefaultCampaign()) {
            return;
        }

        $this->seedLegalDrafts();

        if (Campaign::query()->exists()) {
            return;
        }

        $campaign = $createCampaign->handle('Waitlist', self::SLUG, CampaignType::Waitlist);

        $blocks = [
            ['type' => 'hero', 'content' => ['en' => ['title' => 'Something new is coming', 'body' => 'Join the waitlist and be first to know when it opens.']]],
            ['type' => 'form', 'content' => ['en' => ['label' => 'Email address', 'button' => 'Join the waitlist']]],
            ['type' => 'faq', 'content' => ['en' => ['items' => [
                ['question' => 'What happens after I join?', 'answer' => 'We email you once, when your access opens.'],
                ['question' => 'Can I leave the list?', 'answer' => 'Yes, at any time, from the link in any email we send.'],
            ]]]],
        ];

        foreach ($blocks as $position => $block) {
            $campaign->blocks()->create([...$block, 'position' => $position]);
        }
    }

    /**
     * Draft privacy and terms pages, when missing. They describe what the kit itself stores and leave every
     * site-specific fact in [brackets]; they stay drafts (served as 404) until someone reviews, completes and
     * publishes them. The kit does not publish legal text nobody has read.
     */
    private function seedLegalDrafts(): void
    {
        $drafts = [
            Page::PRIVACY => ['Privacy notice', [
                ['heading' => 'Template', 'paragraphs' => ['Replace every [bracketed] item, have this reviewed, then publish it. Until then it is not shown.']],
                ['heading' => 'Who we are', 'paragraphs' => ['[Your organisation\'s legal name and address] is responsible for the personal data described here. Contact: [an address you read].']],
                ['heading' => 'What we keep', 'paragraphs' => [
                    'When you join a waitlist: your email address, the language of the page, when you joined, where you came from (a referral code, the referring site and any campaign tags in the link), and who referred you.',
                    'When you send a message: your name, email address, company if given, topic and message, and when you agreed to this notice.',
                ]],
                ['heading' => 'Why', 'paragraphs' => ['To tell you when access opens and to answer your message. [Your lawful basis, for example consent.]']],
                ['heading' => 'Who else receives it', 'paragraphs' => ['[The services sign-ups are forwarded to, for example a mailing service, and your email provider.]']],
                ['heading' => 'How long', 'paragraphs' => ['[How long you keep sign-ups and messages.]']],
                ['heading' => 'Your rights', 'paragraphs' => ['You can ask for a copy of your data or for it to be erased at any time by writing to [an address you read].']],
            ]],
            Page::TERMS => ['Terms of use', [
                ['heading' => 'Template', 'paragraphs' => ['Replace every [bracketed] item, have this reviewed, then publish it. Until then it is not shown.']],
                ['heading' => 'The waitlist', 'paragraphs' => ['Joining the waitlist does not guarantee access, a date or a price. [Your terms.]']],
                ['heading' => 'Contact', 'paragraphs' => ['[Your organisation\'s legal name and an address you read.]']],
            ]],
        ];

        foreach ($drafts as $slug => [$title, $sections]) {
            if (Page::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $page = Page::query()->create(['slug' => $slug, 'kind' => PageKind::Legal, 'status' => PageStatus::Draft, 'title' => ['en' => $title]]);
            foreach ($sections as $position => $section) {
                $page->blocks()->create(['type' => 'text', 'position' => $position, 'content' => ['en' => $section]]);
            }
        }
    }
}
