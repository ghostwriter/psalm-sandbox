<?php

declare(strict_types=1);

namespace Ghostwriter\wip\Tests\Unit;

use Ghostwriter\wip\Foo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Foo::class)]
final class FooTest extends TestCase
{
    public function test(): void
    {
        self::assertTrue((new Foo())->test());
    }
}
