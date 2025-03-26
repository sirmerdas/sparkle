<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPreparedSets(codeQuality: true, deadCode: true, naming: true, typeDeclarations: true);
