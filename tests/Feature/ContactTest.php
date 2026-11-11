<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Blade;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Mail\EnquiryNotice;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

beforeEach(function (): void {
    Mail::fake();
    config()->set('app.name', 'Northwind');
    config()->set('laranail.waitlist-kit.contact.to', 'support@example.org');
});

function enquiry(array $overrides = []): array
{
    return [...[
        'name'    => 'Dana Lee',
        'email'   => 'Dana@Example.com',
        'topic'   => 'sales',
        'message' => 'We have twelve hosts and want a demo.',
        'agree'   => '1',
    ], ...$overrides];
}

it('keeps every enquiry and mails the inbox with the visitor as Reply-To', function (): void {
    test()->postJson('/waitlist/contact', enquiry())->assertCreated()->assertHeader('Cache-Control', 'no-store, private');
    test()->postJson('/waitlist/contact', enquiry())->assertCreated();

    expect(Enquiry::query()->count())->toBe(2, 'Every enquiry is kept, unlike a list.')
        ->and(Enquiry::query()->firstOrFail()->email)->toBe('dana@example.com');

    Mail::assertQueued(EnquiryNotice::class, 2);
    Mail::assertQueued(EnquiryNotice::class, function (EnquiryNotice $mail): bool {
        $mail->assertHasReplyTo('dana@example.com', 'Dana Lee');
        $mail->assertHasSubject('[Sales] Dana Lee via Northwind');
        $mail->assertSeeInText('We have twelve hosts and want a demo.');

        return $mail->hasTo('support@example.org');
    });
});

it('keeps enquiries without mailing when no inbox is set', function (): void {
    config()->set('laranail.waitlist-kit.contact.to');

    test()->postJson('/waitlist/contact', enquiry())->assertCreated();

    expect(Enquiry::query()->count())->toBe(1);
    Mail::assertNothingQueued();
});

it('holds an enquiry to the configured rules', function (): void {
    test()->postJson('/waitlist/contact', ['name' => 'D', 'email' => 'nope', 'topic' => 'gossip', 'message' => 'short'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'topic', 'message', 'agree']);

    config()->set('laranail.waitlist-kit.contact.company', 'required');
    test()->postJson('/waitlist/contact', enquiry())->assertJsonValidationErrors('company');

    config()->set('laranail.waitlist-kit.contact.consent', false);
    config()->set('laranail.waitlist-kit.contact.company', 'off');
    config()->set('laranail.waitlist-kit.contact.topics', ['press']);
    test()->postJson('/waitlist/contact', enquiry(['agree' => null, 'topic' => 'press']))->assertCreated();

    expect(Enquiry::query()->count())->toBe(1);
});

it('records consent with the privacy notice it was given against', function (): void {
    $privacy = Page::factory()->legal(Page::PRIVACY)->published()->create();

    test()->postJson('/waitlist/contact', enquiry())->assertCreated();

    expect(Enquiry::query()->sole()->consent)->toHaveKeys(['agreed_at', 'privacy_notice'])
        ->and(Enquiry::query()->sole()->consent['privacy_notice'])->toBe($privacy->updated_at?->toIso8601String());
});

it('links an enquiry to the live campaign page it came from', function (): void {
    $campaign = liveCampaign();

    test()->postJson('/waitlist/contact', enquiry(['campaign' => 'launch']))->assertCreated();
    test()->postJson('/waitlist/contact', enquiry(['campaign' => 'nowhere']))->assertJsonValidationErrors('campaign');

    expect(Enquiry::query()->sole()->campaign_id)->toBe($campaign->id)
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Enquiry)->count())->toBe(1);
});

it('tells a filled honeypot it worked, and keeps nothing', function (): void {
    test()->postJson('/waitlist/contact', enquiry(['website' => 'spam']))->assertCreated();

    expect(Enquiry::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('limits enquiries per visitor', function (): void {
    config()->set('laranail.waitlist-kit.contact.per_minute', 2);

    test()->postJson('/waitlist/contact', enquiry())->assertCreated();
    test()->postJson('/waitlist/contact', enquiry())->assertCreated();
    test()->postJson('/waitlist/contact', enquiry())->assertTooManyRequests();
});

it('sends a form without JavaScript back to its own path, never to another site', function (): void {
    test()->post('/waitlist/contact', enquiry(['return' => '/about']))->assertRedirect('/about')->assertSessionHas('laranail/waitlist-kit.enquiry-sent');
    test()->post('/waitlist/contact', enquiry(['return' => '//evil.example/x']))->assertRedirect('/');
    test()->post('/waitlist/contact', enquiry(['return' => 'https://evil.example/x']))->assertRedirect('/');
    test()->post('/waitlist/contact', enquiry(['email' => 'nope', 'return' => '/about']))->assertRedirect('/about')->assertSessionHasErrors('email');
});

it('answers 404 with the contact form switched off', function (): void {
    config()->set('laranail.waitlist-kit.contact.enabled', false);

    test()->postJson('/waitlist/contact', enquiry())->assertNotFound();
    expect(Blade::render('<x-laranail-waitlist-kit::contact-form />'))->toBe('');
});

it('renders a headless form: no classes, a data-part contract, and the privacy link once published', function (): void {
    $html = Blade::render('<x-laranail-waitlist-kit::contact-form class="my-form" campaign="launch" return="/thanks" />');

    expect($html)->toContain('data-scope="contact-form"')
        ->toContain('class="my-form"')
        ->toContain('data-part="input"')
        ->toContain('name="campaign" value="launch"')
        ->toContain('name="return" value="/thanks"')
        ->toContain('I agree to the privacy notice.')
        ->not->toMatch('/<(input|label|select|textarea|button|div|p)[^>]*\sclass=/');

    $privacy = Page::factory()->legal(Page::PRIVACY)->published()->create();
    expect(Blade::render('<x-laranail-waitlist-kit::contact-form />'))
        ->toContain('href="' . route('laranail-waitlist-kit.page.show', $privacy) . '"');
});
