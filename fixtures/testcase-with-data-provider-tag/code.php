<?php

declare(strict_types=1);

namespace Vendor\Project;

use Generator;
use PHPUnit\Framework\TestCase;

final class FooTest extends TestCase
{
    public static function singleQuotes(): Generator
    {
        yield from ['foo' => ['bar']];
    }

    /** @dataProvider singleQuotes */
    public function testFixture(string $fixture): void
    {
        self::assertSame('bar', $fixture);
    }
}
