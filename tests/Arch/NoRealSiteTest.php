<?php

declare(strict_types=1);

/**
 * A reusable kit names no real site: every host in the repository is a reserved example domain, a service the
 * kit talks to, or one of the project's own homes. A site's own values belong in that site's .env and theme.
 */
const ALLOWED_HOSTS = [
    'localhost', '127.0.0.1', 'github.com', 'img.shields.io', 'opensource.simtabi.com', 'simtabi.com',
    'laravel.com', 'getcomposer.org', 'www.php.net', 'pestphp.com', 'keepachangelog.com', 'semver.org',
    'www.contributor-covenant.org', 'api.brevo.com', 'developers.brevo.com', 'alpinejs.dev', 'tabler.io', 'repo.packagist.org', 'ploi.io', 'symfony.com', 'www.w3.org',
];

/** @return list<string> */
function hostsIn(string $text): array
{
    preg_match_all('#(?:https?://|@)([a-z0-9-]+(?:\.[a-z0-9-]+)+)#i', $text, $matches);

    return array_values(array_unique(array_map(strtolower(...), $matches[1])));
}

function allowedHost(string $host): bool
{
    return in_array($host, ALLOWED_HOSTS, true)
        || preg_match('/(^|\.)example\.(com|org|net)$|\.(test|example|invalid|localhost)$/', $host) === 1
        || preg_match('/^\d+(\.\d+)+$/', $host) === 1
        || str_ends_with($host, '.github.com');
}

it('names no real site anywhere in the repository', function (): void {
    $root = dirname(__DIR__, 2);
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = $file->getPathname();
        if ($file->isFile() && preg_match('#/(vendor|node_modules|\.git|\.phpunit\.cache|build)/|composer\.(local\.)?lock$|composer\.local\.json$|package-lock\.json$#', $path) !== 1) {
            $files[] = $path;
        }
    }
    expect(count($files))->toBeGreaterThanOrEqual(30);

    $named = [];
    foreach ($files as $path) {
        foreach (hostsIn((string) file_get_contents($path)) as $host) {
            if (! allowedHost($host)) {
                $named[] = substr($path, strlen($root) + 1) . ': ' . $host;
            }
        }
    }

    expect($named)->toBe([]);
});

it('can fail', function (): void {
    $real = 'acme' . '.co';
    expect(hostsIn("see https://shop.{$real} or write to hi@{$real}"))->toBe(["shop.{$real}", $real])
        ->and(allowedHost("shop.{$real}"))->toBeFalse()
        ->and(allowedHost('app.example.org'))->toBeTrue();
});
