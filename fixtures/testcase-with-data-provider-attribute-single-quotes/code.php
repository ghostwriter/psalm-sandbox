<?php

declare(strict_types=1);

namespace Vendor\Project;

use Generator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class FooTest extends TestCase
{
    public static function singleQuotes(): Generator
    {
        yield from ['foo' => ['bar']];
    }

    #[DataProvider('singleQuotes')]
    public function testFixture(string $fixture): void
    {
        self::assertSame('bar', $fixture);
    }
}
