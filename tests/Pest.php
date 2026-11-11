<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Simtabi\Laranail\WaitlistKit\Tests\TestCase;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;

// Feature tests get a booted application with the kit's migrations run; Unit and Arch tests do not.
uses(TestCase::class)->in('Feature');

/** A draft waitlist campaign, for tests that need one. */
function campaign(string $slug = 'launch'): Campaign
{
    return Campaign::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'type' => CampaignType::Waitlist,
    ]);
}

/** A live campaign the public routes answer for. */
function liveCampaign(string $slug = 'launch', array $locales = ['en']): Campaign
{
    return Campaign::factory()->live()->create(['slug' => $slug, 'locales' => $locales]);
}

/**
 * Signs in to the backend the way a site would in each sign-in mode: an application user listed in admin.emails
 * (host), or one of the kit's own administrators (bundled).
 */
function signInAdmin(string $mode = 'host'): void
{
    if ($mode === 'bundled') {
        config()->set('laranail.waitlist-kit.auth.mode', 'bundled');
        test()->actingAs(Admin::factory()->create(['email' => 'owner@example.org']), KitConfig::BUNDLED_GUARD);

        return;
    }

    config()->set('laranail.waitlist-kit.auth.mode', 'host');
    config()->set('laranail.waitlist-kit.admin.emails', ['owner@example.org']);
    test()->actingAs(new GenericUser(['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.org']), 'web');
}

/**
 * What a page's ids and references get wrong: an id used twice (a label or an error then points at the first one
 * only), or a label `for`, `aria-describedby` or `aria-labelledby` naming an id that is not there.
 *
 * @return list<string> what is wrong, as sentences
 */
function referenceProblems(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $problems = [];
    $ids = [];

    foreach ($xpath->query('//*[@id]') ?: [] as $element) {
        assert($element instanceof DOMElement);
        $ids[] = $element->getAttribute('id');
    }
    foreach (array_keys(array_filter(array_count_values($ids), static fn (int $count): bool => $count > 1)) as $id) {
        $problems[] = 'the id ' . $id . ' used more than once';
    }

    foreach ($xpath->query('//label[@for] | //*[@aria-describedby] | //*[@aria-labelledby]') ?: [] as $element) {
        assert($element instanceof DOMElement);
        $named = trim($element->getAttribute('for') . ' ' . $element->getAttribute('aria-describedby') . ' ' . $element->getAttribute('aria-labelledby'));
        foreach (preg_split('/\s+/', $named) ?: [] as $id) {
            if ($id !== '' && ! in_array($id, $ids, true)) {
                $problems[] = 'a reference to the missing id ' . $id;
            }
        }
    }

    return $problems;
}
