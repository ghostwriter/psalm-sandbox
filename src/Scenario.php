<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Ghostwriter\PsalmPluginTester\Path\Directory\Fixture;

final class Scenario
{
    public const PHP_81 = '8.1';

    public const PHP_82 = '8.2';

    public const PHP_83 = '8.3';

    public const PHP_ANY = '*';

    public const PHP_CURRENT_VERSION = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

    public const PHP_VERSIONS = [self::PHP_81, self::PHP_82, self::PHP_83, self::PHP_ANY, self::PHP_CURRENT_VERSION];

    public const PSALM_40 = '4.0';

    public const PSALM_50 = '5.0';

    public const PSALM_VERSIONS = [self::PSALM_40, self::PSALM_50];

    public function __construct(
        private readonly Fixture $fixture,
        //        private readonly string $phpVersion,
        //        private readonly string $psalmVersion,
    ) {
    }

    public function getCurrentPhpVersion(): string
    {
        return self::PHP_CURRENT_VERSION;
    }
}
