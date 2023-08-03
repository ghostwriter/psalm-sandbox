<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Tests\Unit;

use Generator;
use Ghostwriter\PHPUnitPsalmPlugin\PHPUnitPlugin;
use Ghostwriter\PsalmPluginTester\Expectation;
use Ghostwriter\PsalmPluginTester\Fixture;
use Ghostwriter\PsalmPluginTester\PluginTester;
use Ghostwriter\PsalmPluginTester\PluginTestResult;
use Ghostwriter\PsalmPluginTester\Version;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Expectation::class)]
#[CoversClass(Fixture::class)]
#[CoversClass(PHPUnitPlugin::class)]
#[CoversClass(PluginTester::class)]
#[CoversClass(PluginTestResult::class)]
#[CoversClass(Version::class)]
final class PHPUnitPluginTest extends TestCase
{
    private bool $done = false;

    private PluginTester $pluginTester;

    protected function setUp(): void
    {
        $this->pluginTester = new PluginTester();
    }

    protected function tearDown(): void
    {
        Assert::assertTrue($this->done);
    }

    public static function fixtureDataProvider(): Generator
    {
        yield from PluginTester::yieldFixtures(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixture'
        );
    }

    #[DataProvider('fixtureDataProvider')]
    public function testPlugin(Fixture $fixture): void
    {
        foreach ([Version::PHP_80, Version::PHP_81, Version::PHP_82] as $phpVersion) {
            $this->pluginTester->testPlugin(PHPUnitPlugin::class, $fixture, $phpVersion);
        }

        $this->done = true;
    }
}
