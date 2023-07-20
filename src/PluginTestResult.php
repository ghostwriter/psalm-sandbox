<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Ghostwriter\Json\Json;
use Ghostwriter\PsalmPluginTester\Path\Directory\Fixture;
use Ghostwriter\PsalmPluginTester\Value\Expectation;
use PHPUnit\Framework\Assert;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\PluginFileExtensionsInterface;
use Psalm\Plugin\PluginInterface;

final class PluginTestResult
{
    /**
     * @param class-string<PluginEntryPointInterface|PluginFileExtensionsInterface|PluginInterface> $pluginClass
     */
    public function __construct(
        private readonly string $pluginClass,
        private readonly Fixture $fixture,
        private readonly ShellResult $shellResult,
    ) {
    }

    public function assertExitCode(int $expectedExitCode): void
    {
        $actualExitCode = $this->shellResult->getExitCode();

        Assert::assertSame(
            $expectedExitCode,
            $actualExitCode,
            sprintf(
                'Expected exit code %d, got %d',
                $expectedExitCode,
                $actualExitCode
            )
        );
    }

    public function assertExpectations(): self
    {
        $output = $this->shellResult->getOutput() . $this->shellResult->getErrorOutput();
        $errors = array_map(
            static fn (
                array $expectation
            ): Expectation => new Expectation(
                $expectation['file_name'],
                $expectation['type'],
                $expectation['message']
            ),
            Json::decode($output)
        );

        Assert::assertSame(
            Json::encode($this->fixture->getProjectRootDirectory()->getExpectationsJsonFile()->unwrap()->getExpectations()),
            Json::encode($errors)
        );

        return $this;
    }

    public function getFixture(): Fixture
    {
        return $this->fixture;
    }

    public function getPluginClass(): string
    {
        return $this->pluginClass;
    }

    public function getShellResult(): ShellResult
    {
        return $this->shellResult;
    }
}
