<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Tests\Unit;

use Ghostwriter\PsalmPluginTester\Fixture;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileProvider;

#[CoversClass(Fixture::class)]
final class FixtureTest extends TestCase
{
    private Fixture $fixture;

    private string $fixtureDirectory;

    private string $vendorDirectory;

    protected function setUp(): void
    {
        $this->fixtureDirectory = dirname(__FILE__, 2) . '/Fixture/black-lives-matter';

        $this->vendorDirectory = dirname($this->fixtureDirectory, 3) . '/vendor';

        $this->fixture = new Fixture(
            $this->fixtureDirectory,
            $this->vendorDirectory,
        );
    }

    public function testFixtureFakeFileProvider(): void
    {
        Assert::assertInstanceOf(
            FakeFileProvider::class,
            $this->fixture
        );
    }

    public function testFixtureFileProvider(): void
    {
        Assert::assertInstanceOf(
            FileProvider::class,
            $this->fixture
        );
    }

    public function testFixtureProjectDirectory(): void
    {
        Assert::assertSame($this->fixtureDirectory, $this->fixture->getProjectDirectory());
    }

    public function testFixtureVendorDirectory(): void
    {
        Assert::assertSame(
            $this->vendorDirectory,
            $this->fixture->getVendorDirectory()
        );
    }
}
