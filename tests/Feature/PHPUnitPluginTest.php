<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Tests\Feature;

use Generator;
use Ghostwriter\PHPUnitPsalmPlugin\Plugin;
use Ghostwriter\PsalmPluginTester\Expectation;
use Ghostwriter\PsalmPluginTester\Fixture;
use Ghostwriter\PsalmPluginTester\PluginTester;
use Ghostwriter\PsalmPluginTester\PluginTestResult;
use Ghostwriter\PsalmPluginTester\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Expectation::class)]
#[CoversClass(Fixture::class)]
#[CoversClass(Plugin::class)]
#[CoversClass(PluginTester::class)]
#[CoversClass(PluginTestResult::class)]
#[CoversClass(Version::class)]
final class PHPUnitPluginTest extends TestCase
{
    private PluginTester $pluginTester;

    protected function setUp(): void
    {
        $this->pluginTester = new PluginTester();
    }

    public static function fixtureDataProvider(): Generator
    {
        yield from PluginTester::yieldFixture(__DIR__);
    }

    #[DataProvider('fixtureDataProvider')]
    public function testPlugin(Fixture $fixture): void
    {
        foreach ([Version::PHP_80, Version::PHP_81, Version::PHP_82] as $phpVersion) {
            $this->pluginTester->testPlugin(Plugin::class, $fixture, $phpVersion);
        }
    }
}
