<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/database', __DIR__ . '/config'])
    // Pinned to the php85 set, matching the ^8.5 floor.
    ->withSets([LevelSetList::UP_TO_PHP_85])
    ->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true)
    // Fixtures stand in for an application's own classes; dead-code rules would strip the empty run() Laravel needs.
    ->withSkip([__DIR__ . '/tests/Fixtures'])
    ->withImportNames(removeUnusedImports: true);
