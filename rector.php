<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/database', __DIR__ . '/config'])
    // Pinned to the php85 set, matching the ^8.5 floor.
    ->withSets([LevelSetList::UP_TO_PHP_85])
    ->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true)
    ->withImportNames(removeUnusedImports: true);
