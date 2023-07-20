<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Tests\Unit;

use Generator;
use Ghostwriter\Json\Json;
use Ghostwriter\PsalmPluginTester\Path\Directory\Fixture;
use Ghostwriter\PsalmPluginTester\PluginTester;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psalm\PhpUnitPlugin\Plugin;

#[CoversClass(PluginTester::class)]
final class PsalmPhpUnitPluginTest extends TestCase
{
    private PluginTester $pluginTester;

    protected function setUp(): void
    {
        $this->pluginTester = new PluginTester(Plugin::class);
    }

    public static function fixtureDataProvider(): Generator
    {
        yield from PluginTester::yieldFixtures(
            dirname(__FILE__, 2) . '/Fixture'
        );
    }

    /** @dataProvider fixtureDataProvider */
    public function testPlugin(Fixture $fixture): void
    {
        $this->pluginTester->test($fixture);
    }
}
