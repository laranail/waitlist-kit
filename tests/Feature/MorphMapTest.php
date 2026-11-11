<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Models\Model;
use Simtabi\Laranail\WaitlistKit\Support\MorphMap;
use Illuminate\Database\Eloquent\Relations\Relation;
use Simtabi\Laranail\WaitlistKit\Models\Integration;

/**
 * The kit's morph map, enforced for the kit's own models: every model is mapped under the package's prefix, rows store
 * the alias rather than a class, and every polymorphic column is an `*able` pair. Relation::enforceMorphMap() is not
 * used: it is global, and would refuse the application's own unmapped models.
 */
it('maps every kit model under the package prefix', function (): void {
    $models = [];
    foreach (glob(dirname(__DIR__, 2) . '/src/Models/*.php') ?: [] as $file) {
        $class = 'Simtabi\\Laranail\\WaitlistKit\\Models\\' . basename($file, '.php');
        if ($class !== Model::class) {
            $models[] = $class;
        }
    }
    expect($models)->toHaveCount(count(MorphMap::MAP));

    $map = Relation::morphMap();
    foreach ($models as $class) {
        $alias = array_search($class, $map, true);
        expect($alias)->toBeString("{$class} is not in the morph map")
            ->and((string) $alias)->toStartWith(MorphMap::PREFIX)
            ->and((new $class)->getMorphClass())->toBe($alias);
    }
});

it('stores the alias, never a class name, in *able_type columns', function (): void {
    $campaign = campaign();
    $campaign->blocks()->create(['type' => 'hero']);
    $campaign->integrations()->create(['driver' => 'app', 'settings' => []]);

    expect(DB::table('laranail_waitlist_kit_blocks')->value('blockable_type'))->toBe('laranail-waitlist-kit.campaign')
        ->and(DB::table('laranail_waitlist_kit_integrations')->value('integrable_type'))->toBe('laranail-waitlist-kit.campaign')
        ->and(Block::query()->firstOrFail()->blockable?->is($campaign))->toBeTrue();
});

it('merges into the application\'s map instead of replacing it', function (): void {
    Relation::morphMap(['app.thing' => stdClass::class]);

    expect(Relation::morphMap())->toHaveKey('app.thing')
        ->toHaveKey(MorphMap::PREFIX . 'campaign');
});

it('names every polymorphic column pair *able', function (): void {
    $migration = (string) file_get_contents(dirname(__DIR__, 2) . '/database/migrations/2026_11_11_000000_create_waitlist_kit_tables.php');
    preg_match_all("/(?:ulid|uuid|nullableUlid|nullableUuid|nullable)?[Mm]orphs\\('([a-z_]+)'/", $migration, $morphs);
    expect($morphs[1])->toBe(['blockable', 'eventable', 'integrable']);

    foreach ($morphs[1] as $name) {
        expect($name)->toEndWith('able');
    }

    // And every MorphTo relation in the models is named after its column.
    foreach (glob(dirname(__DIR__, 2) . '/src/Models/*.php') ?: [] as $file) {
        preg_match_all('/function (\w+)\(\): MorphTo/', (string) file_get_contents($file), $relations);
        foreach ($relations[1] as $relation) {
            expect($relation)->toEndWith('able', basename($file) . " has a MorphTo named {$relation}");
        }
    }
});

it('treats an integration without an owner as serving every campaign', function (): void {
    $global = Integration::factory()->create();

    expect($global->integrable_type)->toBeNull()
        ->and($global->integrable)->toBeNull();
});
