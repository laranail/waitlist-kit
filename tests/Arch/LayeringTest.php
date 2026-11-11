<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Models\Model;

/**
 * The dependency rule, enforced: the domain layers take what they need through constructors; only the provider
 * and the commands (the framework edges) may reach the container, and migrations may use Schema.
 */
$globals = ['config', 'env', 'request', 'app', 'resolve', 'session', 'cache', 'logger', 'report', 'now'];

arch('actions take their dependencies by injection')
    ->expect('Simtabi\Laranail\WaitlistKit\Actions')
    ->not->toUse([...$globals, 'Illuminate\Support\Facades']);

arch('support classes take their dependencies by injection')
    ->expect('Simtabi\Laranail\WaitlistKit\Support')
    ->not->toUse([...$globals, 'Illuminate\Support\Facades']);

arch('models reach no facade or global helper')
    ->expect('Simtabi\Laranail\WaitlistKit\Models')
    ->not->toUse([...$globals, 'Illuminate\Support\Facades']);

arch('enums and exceptions stay free of the framework')
    ->expect(['Simtabi\Laranail\WaitlistKit\Enums', 'Simtabi\Laranail\WaitlistKit\Exceptions'])
    ->not->toUse('Illuminate');

arch('every class is final or abstract, and strict')
    ->expect('Simtabi\Laranail\WaitlistKit')
    ->classes()
    ->toBeFinal()
    ->ignoring(Model::class);

arch('every file declares strict types')
    ->expect('Simtabi\Laranail\WaitlistKit')
    ->toUseStrictTypes();

it('keeps every service provider in a Providers directory and namespace', function (): void {
    // A recursive walk: PHP's glob() reads `**` as one directory level, so it misses a provider at the root.
    $providers = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src', FilesystemIterator::SKIP_DOTS)) as $file) {
        if (str_ends_with($file->getFilename(), 'ServiceProvider.php')) {
            $providers[] = $file->getPathname();
        }
    }
    expect($providers)->not->toBeEmpty();

    foreach ($providers as $file) {
        expect(basename(dirname($file)))->toBe('Providers')
            ->and((string) file_get_contents($file))->toContain('namespace Simtabi\\Laranail\\WaitlistKit\\Providers;');
    }
});
