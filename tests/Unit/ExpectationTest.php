<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Tests\Unit;

use Ghostwriter\Json\Json;
use Ghostwriter\PsalmPluginTester\Expectation;
use JsonSerializable;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stringable;

#[CoversClass(Expectation::class)]
final class ExpectationTest extends TestCase
{
    private Expectation $expectation;

    private string $file;

    private string $message;

    private string $severity;

    private string $type;

    protected function setUp(): void
    {
        $this->file = __FILE__;
        $this->message = 'Black Lives Matter';
        $this->severity = 'notice';
        $this->type = 'black-lives-matter';

        $this->expectation = new Expectation(
            $this->file,
            $this->message,
            $this->severity,
            $this->type,
        );
    }

    public function testExpectation(): void
    {
        Assert::assertInstanceOf(
            JsonSerializable::class,
            $this->expectation
        );

        Assert::assertInstanceOf(
            Stringable::class,
            $this->expectation
        );
    }

    public function testFile(): void
    {
        Assert::assertSame($this->file, $this->expectation->getFile());
    }

    public function testJsonSerialize(): void
    {
        Assert::assertSame(
            $this->file,
            $this->expectation->jsonSerialize()['file']
        );

        Assert::assertSame(
            $this->message,
            $this->expectation->jsonSerialize()['message']
        );

        Assert::assertSame(
            $this->severity,
            $this->expectation->jsonSerialize()['severity']
        );

        Assert::assertSame(
            $this->type,
            $this->expectation->jsonSerialize()['type']
        );
    }

    public function testMessage(): void
    {
        Assert::assertSame($this->message, $this->expectation->getMessage());
    }

    public function testSeverity(): void
    {
        Assert::assertSame($this->severity, $this->expectation->getSeverity());
    }

    public function testToString(): void
    {
        Assert::assertSame(
            Json::encode($this->expectation->jsonSerialize()),
            (string) $this->expectation
        );
    }

    public function testType(): void
    {
        Assert::assertSame($this->type, $this->expectation->getType());
    }
}
