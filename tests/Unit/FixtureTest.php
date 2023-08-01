<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Tests\Feature;

use Ghostwriter\PsalmPluginTester\Fixture;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Provider\FakeFileProvider;

#[CoversClass(Fixture::class)]
final class FixtureTest extends TestCase
{
    private Fixture $fixture;

    private string $fixturePath;

    protected function setUp(): void
    {
        $this->fixturePath = dirname(__FILE__, 2) . '/Fixture/black-lives-matter';

        $this->fixture = new Fixture(
            $this->fixturePath,
        );
    }

    public function testFixtureFakeFileProvider(): void
    {
        Assert::assertInstanceOf(
            FakeFileProvider::class,
            $this->fixture
        );
    }

    public function testFixtureName(): void
    {
        Assert::assertSame('black-lives-matter', $this->fixture->getName());
    }

    public function testFixturePath(): void
    {
        Assert::assertSame($this->fixturePath, $this->fixture->getPath());
    }
}
