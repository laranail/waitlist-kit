<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Enums\AdminAppearance;

/** The <html> tag of a response, where the colour scheme is rendered. */
function htmlTag(string $html): string
{
    preg_match('/<html[^>]*>/', $html, $tag);

    return $tag[0] ?? '';
}

it('follows the system by default, with the head script before the stylesheet', function (): void {
    signInAdmin('host');
    $html = (string) $this->get('/waitlist-admin')->assertOk()->getContent();

    expect(htmlTag($html))->toContain('data-bs-theme="light" data-wk-appearance="system"')
        // The script must be classic and come first: a module is deferred, and after the stylesheet it would flash.
        ->and($html)->toMatch('#<script src="[^"]*/assets/js/admin-theme-[^"]+\.js"></script>\s*<link[^>]+admin-[^"]+\.css#')
        ->and($html)->not->toMatch('#<script type="module"[^>]*admin-theme#')
        ->and(substr_count($html, 'name="appearance"'))->toBe(3)
        ->and($html)->toContain('value="system" class="btn btn-sm btn-primary" aria-pressed="true"');
});

it('renders a chosen scheme on the server, so the first paint is already right', function (string $choice, string $theme): void {
    signInAdmin('host');

    $html = (string) $this->withCookie(AdminAppearance::COOKIE, $choice)->get('/waitlist-admin/campaigns')->assertOk()->getContent();

    expect(htmlTag($html))->toContain('data-bs-theme="' . $theme . '" data-wk-appearance="' . AdminAppearance::fromCookie($choice)->value . '"');
})->with([
    'dark'     => ['dark', 'dark'],
    'light'    => ['light', 'light'],
    'tampered' => ['<script>', 'light'],
]);

it('saves the choice in a cookie and returns to the same screen', function (): void {
    signInAdmin('host');

    $this->post('/waitlist-admin/appearance', ['appearance' => 'dark', 'return' => '/waitlist-admin/campaigns?x=1'])
        ->assertRedirect('/waitlist-admin/campaigns?x=1')
        ->assertCookie(AdminAppearance::COOKIE, 'dark');

    $this->post('/waitlist-admin/appearance', ['appearance' => 'dark', 'return' => '//elsewhere.example/x'])->assertRedirect('/');
    $this->post('/waitlist-admin/appearance', ['appearance' => 'dark'])->assertRedirect('/waitlist-admin');
    $this->post('/waitlist-admin/appearance', ['appearance' => 'sepia'])->assertSessionHasErrors('appearance');
});

it('needs a signed-in administrator to change it', function (): void {
    $this->post('/waitlist-admin/appearance', ['appearance' => 'dark'])->assertCookieMissing(AdminAppearance::COOKIE);
});

it('uses the scheme on the bundled sign-in page too', function (): void {
    config()->set('laranail.waitlist-kit.auth.mode', 'bundled');

    $html = (string) $this->withCookie(AdminAppearance::COOKIE, 'dark')->get('/waitlist-admin/login')->assertOk()->getContent();

    expect(htmlTag($html))->toContain('data-bs-theme="dark"')->and($html)->toContain('admin-theme-');
});
