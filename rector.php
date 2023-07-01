<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/vendor/ghostwriter/coding-standard/rector.php');
    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    $rectorConfig->paths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/ecs.php', __DIR__ . '/rector.php']);
    $rectorConfig->skip([__DIR__ . '*/tests/Fixture/*', __DIR__ . '*/vendor/*']);
};
