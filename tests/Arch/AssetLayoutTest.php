<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Themes\KitThemes;

/**
 * Web assets live in assets/<kind>/, in the source and in the build (the estate's rule): the build output is the
 * manifest plus hashed files under assets/{js,css,fonts,images,media,favicons}/, and the sources sit under
 * resources/assets/<kind>/. Each check counts what it inspected and fails below a floor.
 */
const KINDS = ['js', 'css', 'fonts', 'images', 'media', 'favicons'];

/** @return list<string> paths relative to $root */
function filesUnder(string $root): array
{
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        $files[] = substr($file->getPathname(), strlen($root) + 1);
    }

    return $files;
}

it('builds only the manifest and hashed files under assets/<kind>/', function (): void {
    $files = filesUnder(dirname(__DIR__, 2) . '/resources/dist');
    expect(count($files))->toBeGreaterThanOrEqual(4);

    foreach ($files as $file) {
        $ok = $file === 'manifest.json'
            || preg_match('#^assets/(' . implode('|', KINDS) . ')/[\w.-]+-[\w-]{8}\.\w+$#', $file) === 1;
        expect($ok)->toBeTrue("resources/dist/{$file} is outside assets/<kind>/ or has no content hash");
    }
});

it('keeps every asset source under resources/assets/<kind>/', function (): void {
    $files = filesUnder(dirname(__DIR__, 2) . '/resources/assets');
    expect(count($files))->toBeGreaterThanOrEqual(3);

    foreach ($files as $file) {
        expect(preg_match('#^(' . implode('|', KINDS) . ')/#', $file))->toBe(1, "resources/assets/{$file} is outside a kind folder");
    }
});

it('builds every entry each kit theme names', function (): void {
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/resources/dist/manifest.json'), true);

    foreach (KitThemes::all() as $theme) {
        foreach ($theme->entries as $entry) {
            expect($manifest)->toHaveKey($entry);
            expect(dirname(__DIR__, 2) . '/resources/dist/' . $manifest[$entry]['file'])->toBeFile();
        }
    }
});
