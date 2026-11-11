<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\Integration;

/**
 * The backend's structural accessibility, checked on every screen: a language, one h1, a main landmark with a skip
 * link to it, a label for every control, text on every button, captions and header scopes on tables, the current
 * section marked, and no positive tabindex. A manual pass with a screen reader is still the owner's call.
 *
 * @return list<string> what is wrong, as sentences
 */
function accessibilityProblems(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $problems = [];

    if (($xpath->query('//html[@lang]')?->length ?? 0) !== 1) {
        $problems[] = 'no lang on <html>';
    }
    if (($xpath->query('//h1')?->length ?? 0) !== 1) {
        $problems[] = 'not exactly one <h1>';
    }
    if (($xpath->query('//main[@id="content"]')?->length ?? 0) !== 1) {
        $problems[] = 'no <main id="content">';
    }

    foreach ($xpath->query('//input[not(@type="hidden")] | //select | //textarea') ?: [] as $control) {
        assert($control instanceof DOMElement);
        $id = $control->getAttribute('id');
        $labelled = $control->hasAttribute('aria-label')
            || ($id !== '' && ($xpath->query('//label[@for="' . $id . '"]')?->length ?? 0) > 0);
        if (! $labelled && ! in_array($control->getAttribute('type'), ['submit', 'button'], true)) {
            $problems[] = 'an unlabelled control: ' . ($control->getAttribute('name') ?: $control->nodeName);
        }
    }

    foreach ($xpath->query('//button') ?: [] as $button) {
        if (trim($button->textContent) === '') {
            $problems[] = 'a button with no text';
        }
    }

    foreach ($xpath->query('//table') ?: [] as $table) {
        assert($table instanceof DOMElement);
        if (($xpath->query('caption', $table)?->length ?? 0) !== 1) {
            $problems[] = 'a table without a caption';
        }
        if (($xpath->query('thead//th[not(@scope)]', $table)?->length ?? 0) > 0) {
            $problems[] = 'a column header without scope';
        }
    }

    foreach ($xpath->query('//*[@tabindex]') ?: [] as $element) {
        assert($element instanceof DOMElement);
        if ((int) $element->getAttribute('tabindex') > 0) {
            $problems[] = 'a positive tabindex';
        }
    }

    return $problems;
}

it('gives every backend screen its structure', function (string $mode): void {
    signInAdmin($mode);
    $campaign = Campaign::factory()->create(['locales' => ['en', 'es']]);
    $campaign->blocks()->create(['type' => 'faq', 'content' => ['en' => ['items' => [['question' => 'When?', 'answer' => 'Soon']]]]]);
    Subscriber::factory()->create(['campaign_id' => $campaign->id]);
    $enquiry = Enquiry::factory()->create();
    $page = Page::factory()->legal('privacy')->create();
    $page->blocks()->create(['type' => 'text', 'content' => ['en' => ['heading' => 'What we keep']]]);
    $integration = Integration::factory()->create();

    $screens = [
        '/waitlist-admin',
        '/waitlist-admin/campaigns',
        '/waitlist-admin/campaigns/create',
        "/waitlist-admin/campaigns/{$campaign->id}/edit",
        "/waitlist-admin/campaigns/{$campaign->id}/subscribers",
        '/waitlist-admin/enquiries',
        "/waitlist-admin/enquiries/{$enquiry->id}",
        '/waitlist-admin/pages',
        "/waitlist-admin/pages/{$page->id}/edit",
        '/waitlist-admin/integrations',
        '/waitlist-admin/integrations/create',
        "/waitlist-admin/integrations/{$integration->id}/edit",
    ];

    foreach ($screens as $url) {
        $html = (string) $this->get($url)->assertOk()->getContent();

        expect(accessibilityProblems($html))->toBe([], $url)
            ->and($html)->toContain('href="#content"')
            ->and($html)->toContain('aria-current="page"');
    }
    expect($screens)->toHaveCount(12);
})->with(['host', 'bundled']);

it('summarises errors in an alert that links to each field', function (): void {
    signInAdmin('host');

    $html = (string) $this->followingRedirects()->from('/waitlist-admin/campaigns/create')
        ->post('/waitlist-admin/campaigns', ['name' => '', 'slug' => 'Bad Slug', 'type' => 'waitlist', 'locales' => 'en'])
        ->getContent();

    expect($html)->toContain('role="alert"')
        ->toContain('href="#f-name"')
        ->toContain('id="f-name"')
        ->toContain('aria-invalid="true"')
        ->toContain('aria-describedby="f-slug-hint f-slug-error"');
});

it('can fail', function (): void {
    expect(accessibilityProblems('<html><body><h1>a</h1><h1>b</h1><input name="x"><button></button><table><thead><tr><th>A</th></tr></thead></table><div tabindex="2"></div></body></html>'))
        ->toBe([
            'no lang on <html>', 'not exactly one <h1>', 'no <main id="content">', 'an unlabelled control: x',
            'a button with no text', 'a table without a caption', 'a column header without scope', 'a positive tabindex',
        ]);
});
