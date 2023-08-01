<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

final class Version
{

    public const PHP_80 = '8.0';

    public const PHP_81 = '8.1';

    public const PHP_82 = '8.2';

    public const PHP_83 = '8.3';

    public const PHP_CURRENT_VERSION = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

    public const PHP_VERSIONS = [
        self::PHP_80,
        self::PHP_81,
        self::PHP_82,
    ];

    public const PSALM_40 = '4.0';

    public const PSALM_50 = '5.0';

    public const PSALM_VERSIONS = [self::PSALM_40, self::PSALM_50];
}
