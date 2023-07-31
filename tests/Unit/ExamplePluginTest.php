<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Tests\Unit;

use Generator;
use Ghostwriter\ExamplePsalmPlugin\ExamplePlugin;
use Ghostwriter\PsalmPluginTester\Path\Directory\DirectoryTrait;
use Ghostwriter\PsalmPluginTester\Path\Directory\Fixture;
use Ghostwriter\PsalmPluginTester\Path\Directory\ProjectRootDirectory;
use Ghostwriter\PsalmPluginTester\Path\File\ExpectationsJsonFile;
use Ghostwriter\PsalmPluginTester\Path\File\FileTrait;
use Ghostwriter\PsalmPluginTester\Path\File\PsalmXmlFile;
use Ghostwriter\PsalmPluginTester\Path\PathTrait;
use Ghostwriter\PsalmPluginTester\PluginTester;
use Ghostwriter\PsalmPluginTester\PluginTestResult;
use Ghostwriter\PsalmPluginTester\Value\Expectation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DirectoryTrait::class)]
#[CoversClass(Expectation::class)]
#[CoversClass(ExpectationsJsonFile::class)]
#[CoversClass(FileTrait::class)]
#[CoversClass(Fixture::class)]
#[CoversClass(PathTrait::class)]
#[CoversClass(PluginTestResult::class)]
#[CoversClass(PluginTester::class)]
#[CoversClass(ProjectRootDirectory::class)]
#[CoversClass(PsalmXmlFile::class)]
final class ExamplePluginTest extends TestCase
{
    private PluginTester $pluginTester;

    protected function setUp(): void
    {
        $this->pluginTester = new PluginTester(ExamplePlugin::class);
    }

    public static function fixtureDataProvider(): Generator
    {
        yield from PluginTester::yieldFixtures(
            ExamplePlugin::class,
            dirname(__FILE__, 2) . '/Fixture/ExamplePlugin'
        );
    }

    /** @dataProvider fixtureDataProvider */
    public function testPlugin(Fixture $fixture): void
    {
        $result = $this->pluginTester->test($fixture);

        self::assertSame($fixture, $result->getFixture());
    }
}
