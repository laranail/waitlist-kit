<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;

/** The kit's routes as "METHOD uri name", HEAD folded into GET. */
function kitRouteTable(): array
{
    $rows = [];
    foreach (Router::getRoutes()->getRoutes() as $route) {
        /** @var Route $route */
        $name = (string) $route->getName();
        if (! str_starts_with($name, 'laranail-waitlist-kit.')) {
            continue;
        }
        $methods = array_values(array_diff($route->methods(), ['HEAD']));
        $rows[] = implode('|', $methods) . ' ' . $route->uri() . ' ' . $name;
    }
    sort($rows);

    return $rows;
}

it('serves every public route at /waitlist and every backend route at /admin/waitlist, all named', function (): void {
    $table = kitRouteTable();

    expect($table)->toHaveCount(49);
    foreach ($table as $row) {
        [, $uri] = explode(' ', $row);
        $admin = str_contains($row, ' laranail-waitlist-kit.admin.');
        expect($uri)->toStartWith($admin ? 'admin/waitlist' : 'waitlist', $row);
    }

    // Routes the layout and the mail depend on, with their exact paths.
    expect($table)->toContain(
        'GET waitlist/{campaign} laranail-waitlist-kit.campaign.show',
        'POST waitlist/{campaign} laranail-waitlist-kit.campaign.signup',
        'GET waitlist/{campaign}/unsubscribe/{subscriber} laranail-waitlist-kit.campaign.unsubscribe',
        'POST waitlist/{campaign}/unsubscribe/{subscriber} laranail-waitlist-kit.campaign.unsubscribe.store',
        'POST waitlist/api/conversions laranail-waitlist-kit.conversions.store',
        'GET admin/waitlist laranail-waitlist-kit.admin.dashboard',
        'GET admin/waitlist/campaigns/{campaign}/edit laranail-waitlist-kit.admin.campaigns.edit',
        'PUT admin/waitlist/blocks/{block} laranail-waitlist-kit.admin.blocks.update',
        'POST admin/waitlist/pages/{page}/blocks laranail-waitlist-kit.admin.pages.blocks.store',
    );
});

it('answers live validation on the sign-up, the contact form and the backend except its sign-in, and nowhere else public', function (): void {
    $precognitive = collect(Router::getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with((string) $route->getName(), 'laranail-waitlist-kit.')
            && in_array(HandlePrecognitiveRequests::class, $route->gatherMiddleware(), true)
            && ! in_array(HandlePrecognitiveRequests::class, $route->excludedMiddleware(), true))
        ->map(fn (Route $route): string => (string) $route->getName());

    expect($precognitive->reject(fn (string $name): bool => str_starts_with($name, 'laranail-waitlist-kit.admin.'))->sort()->values()->all())
        ->toBe(['laranail-waitlist-kit.campaign.signup', 'laranail-waitlist-kit.contact.store'])
        ->and($precognitive->filter(fn (string $name): bool => str_starts_with($name, 'laranail-waitlist-kit.admin.'))->count())->toBe(37)
        ->and($precognitive->all())->not->toContain('laranail-waitlist-kit.admin.login.store');
});
