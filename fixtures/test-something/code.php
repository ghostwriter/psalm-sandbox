<?php

declare(strict_types=1);

namespace Ghostwriter\ExamplePsalmPlugin\Tests\Unit;

use Generator;
use Ghostwriter\PsalmPlugin\Hook\AbstractHook;
use Ghostwriter\PsalmPlugin\Hook\SuppressMissingConstructorHook;
use Ghostwriter\PsalmPlugin\Plugin;
use Ghostwriter\PsalmPluginTester\Fixture;
use Ghostwriter\PsalmPluginTester\PluginTester;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ObjectTest extends TestCase
{
    private stdClass $subject;

    protected function setUp(): void
    {
        $this->subject = new stdClass();
    }

    public function testObject(): void
    {
        self::assertInstanceOf(
            stdClass::class,
            $this->subject
        );
    }
}
