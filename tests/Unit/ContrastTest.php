<?php

declare(strict_types=1);

/*
 * The shipped colours, measured: text at 4.5:1 and a control's edge at 3:1 (WCAG 1.4.3 and 1.4.11), computed from
 * the stylesheets' own tokens so a re-tuned colour fails here rather than in a reader's eyes. A site's own theme
 * settings are its own to check; the doctor does not judge taste.
 */

/** @return array{float, float, float} */
function contrastRgb(string $hex): array
{
    $hex = ltrim($hex, '#');

    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

/** @param array{float, float, float} $color */
function contrastLuminance(array $color): float
{
    $linear = array_map(static function (float $channel): float {
        $channel /= 255;

        return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }, $color);

    return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
}

/** @param array{float, float, float} $a @param array{float, float, float} $b */
function contrastRatio(array $a, array $b): float
{
    [$light, $dark] = contrastLuminance($a) >= contrastLuminance($b) ? [$a, $b] : [$b, $a];

    return (contrastLuminance($light) + 0.05) / (contrastLuminance($dark) + 0.05);
}

/** $ink at $percent over $page, as `color-mix(in srgb, ink N%, page)` and Tailwind's `ink/N` both paint it. */
/** @return array{float, float, float} */
function contrastMix(string $ink, int $percent, string $page): array
{
    $over = contrastRgb($ink);
    $under = contrastRgb($page);

    return array_map(static fn (int $i): float => $over[$i] * $percent / 100 + $under[$i] * (100 - $percent) / 100, [0, 1, 2]);
}

/** @return array<string, string> the `--name: #hex` tokens of a stylesheet's first :root */
function contrastTokens(string $css): array
{
    preg_match('/:root\s*\{([^}]*)\}/', $css, $root);
    preg_match_all('/--([\w-]+):\s*(#[0-9a-fA-F]{6})/', $root[1] ?? '', $pairs, PREG_SET_ORDER);

    return array_column($pairs, 2, 1);
}

function contrastStylesheet(string $name): string
{
    return (string) file_get_contents(dirname(__DIR__, 2) . '/resources/assets/css/' . $name);
}

it('keeps the backend on the Tabler release whose colours were measured, with its corrections', function (): void {
    // Tabler's colours are oklch and mixed in oklab, so they are measured in a browser rather than here: axe-core 4.10
    // on every backend screen, 2026-11-11, with @tabler/core 1.7.1. These assertions pin that measurement: a Tabler
    // upgrade or a dropped correction fails here, which is the prompt to measure again (docs/accessibility.md).
    $package = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
    $admin = contrastStylesheet('admin.css');

    expect($package['devDependencies']['@tabler/core'] ?? null)->toBe('1.7.1')
        ->and($admin)->toContain('@import "@tabler/core/dist/css/tabler.min.css";');

    $corrections = [
        // measured before: green 2.48, yellow 1.97, secondary 4.19 (tinted badges); info alert 2.83; danger 4.47; table link 2.07
        '/\.wka \.badge\.bg-green-lt \{ color: color-mix\(in oklab, var\(--tblr-green\) 55%, #000\) !important; \}/',
        '/\.wka \.badge\.bg-yellow-lt \{ color: color-mix\(in oklab, var\(--tblr-yellow\) 50%, #000\) !important; \}/',
        '/\.wka \.badge\.bg-secondary-lt \{ color: color-mix\(in oklab, var\(--tblr-secondary\) 60%, #000\) !important; \}/',
        '/\.wka \.badge\.bg-azure-lt \{/',
        '/\.wka \.badge\.bg-red-lt \{/',
        '/\.wka \.alert \{\s*--tblr-alert-color: var\(--tblr-body-color\);/',
        '/\.wka \.btn-danger,\s*\.wka \.btn-ghost-danger,\s*\.wka \.btn-outline-danger \{\s*--tblr-btn-variant: color-mix\(in oklab, var\(--tblr-danger\) 88%, #000\);/',
        '/\.wka \.card-table a:not\(\.btn\) \{\s*text-decoration: underline;/',
        '/\.wka a\.btn-primary,\s*\.wka a\.btn-danger \{\s*color: #fff;/',
    ];
    foreach ($corrections as $rule) {
        expect($admin)->toMatch($rule);
    }
    expect($corrections)->toHaveCount(9);
});

it('keeps both public themes readable with their default colours', function (): void {
    $plain = contrastStylesheet('themes/plain.css');
    $default = contrastStylesheet('themes/default.css');
    $t = contrastTokens($plain);
    expect($t)->toHaveKeys(['wk-primary', 'wk-background', 'wk-text'])
        ->and(contrastTokens($default))->toMatchArray(array_intersect_key($t, array_flip(['wk-primary', 'wk-background', 'wk-text'])));

    expect(contrastRatio(contrastRgb($t['wk-text']), contrastRgb($t['wk-background'])))->toBeGreaterThanOrEqual(4.5)
        ->and(contrastRatio(contrastRgb($t['wk-primary']), contrastRgb($t['wk-background'])))->toBeGreaterThanOrEqual(4.5) // links
        ->and(contrastRatio(contrastRgb('#ffffff'), contrastRgb($t['wk-primary'])))->toBeGreaterThanOrEqual(4.5);          // button labels

    // Muted text and control edges are the text colour mixed into the page.
    preg_match('/--wk-muted: color-mix\(in srgb, var\(--wk-text\) (\d+)%/', $plain, $muted);
    preg_match('/--wk-control-border: color-mix\(in srgb, var\(--wk-text\) (\d+)%/', $plain, $plainEdge);
    preg_match('/\.wk-input, \[data-scope="contact-form"\][^{]*\{\s*@apply[^;]*border-wk-text\/(\d+)/', $default, $defaultEdge);
    preg_match_all('/text-wk-text\/(\d+)/', $default, $defaultMuted);
    expect($muted)->toHaveCount(2)->and($plainEdge)->toHaveCount(2)->and($defaultEdge)->toHaveCount(2)->and($defaultMuted[1])->not->toBeEmpty();

    expect(contrastRatio(contrastMix($t['wk-text'], (int) $muted[1], $t['wk-background']), contrastRgb($t['wk-background'])))->toBeGreaterThanOrEqual(4.5);
    foreach ($defaultMuted[1] as $percent) {
        expect(contrastRatio(contrastMix($t['wk-text'], (int) $percent, $t['wk-background']), contrastRgb($t['wk-background'])))->toBeGreaterThanOrEqual(4.5, "text-wk-text/{$percent}");
    }
    foreach ([(int) $plainEdge[1], (int) $defaultEdge[1]] as $percent) {
        expect(contrastRatio(contrastMix($t['wk-text'], $percent, $t['wk-background']), contrastRgb($t['wk-background'])))->toBeGreaterThanOrEqual(3.0, "control edge at {$percent}%");
    }
});

it('can fail', function (): void {
    expect(contrastRatio(contrastRgb('#777777'), contrastRgb('#ffffff')))->toBeLessThan(4.5)
        ->and(contrastRatio(contrastMix('#111827', 20, '#ffffff'), contrastRgb('#ffffff')))->toBeLessThan(3.0);
});
