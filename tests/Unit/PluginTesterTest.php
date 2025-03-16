<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Tests\Unit;

use CallbackFilterIterator;
use FilesystemIterator;
use Ghostwriter\PsalmPluginTester\Fixture;
use Ghostwriter\PsalmPluginTester\PluginTester;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

#[CoversClass(Fixture::class)]
#[CoversClass(PluginTester::class)]
final class PluginTesterTest extends TestCase
{
    private string $fixturePath;

    private PluginTester $pluginTester;

    protected function setUp(): void
    {
        $this->fixturePath = dirname(__FILE__, 3) . '/fixtures';
    }

    public function testYieldFixture(): void
    {
        foreach (PluginTester::yieldFixture(__DIR__) as $fixtures) {
            Assert::assertIsArray($fixtures);

            foreach ($fixtures as $fixture) {
                Assert::assertInstanceOf(Fixture::class, $fixture);
            }
        }
    }

    public function testYieldsFixtureCount(): void
    {
        Assert::assertSame(1, iterator_count(PluginTester::yieldFixture(__DIR__)));
    }

    public function testYieldsFixtures(): void
    {
        foreach (PluginTester::yieldFixtures($this->fixturePath) as $fixtures) {
            Assert::assertIsArray($fixtures);

            foreach ($fixtures as $fixture) {
                Assert::assertInstanceOf(Fixture::class, $fixture);
            }
        }
    }

    public function testYieldsFixturesCount(): void
    {
        Assert::assertSame(
            self::countDirectories($this->fixturePath),
            iterator_count(PluginTester::yieldFixtures($this->fixturePath))
        );
    }

    /**
     * @psalm-return int<0, max>
     */
    private static function countDirectories(string $directory): int
    {
        return iterator_count(
            new CallbackFilterIterator(
                new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS),
                static fn (SplFileInfo $current): bool => $current->isDir()
            )
        );
    }
}
