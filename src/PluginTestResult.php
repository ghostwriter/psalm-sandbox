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
use Throwable;

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
        $errorOutput = $this->shellResult->getErrorOutput();

        if ($errorOutput !== '') {
            Assert::fail($errorOutput);
        }

        /** @var list<Expectation> $errors */
        $errors = array_map(
            static fn (
                array $expectation
            ): Expectation => new Expectation(
                $expectation['file_name'],
                $expectation['type'],
                $expectation['message']
            ),
            $this->decode($this->shellResult->getOutput())
        );

        $root = $this->fixture->getPath();

        $encode = $this->encode([
            'error' => $errors,
        ]);

        Assert::assertSame(
            $this->fixture->getProjectRootDirectory()
                ->getExpectationsJsonFile()
                ->mapOrElse(
                    fn ($file) => $this->encode([
                        'error' => $file->getExpectations(),
                    ]),
                    static function () use ($root, $encode): string {
                        file_put_contents($root . '/expectations.json', $encode . PHP_EOL);

                        return $encode;
                    }
                ),
            $encode
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

    private function decode(string $data): array
    {
        try {
            return Json::decode($data);
        } catch (Throwable $e) {
            Assert::fail($e->getMessage() . PHP_EOL . var_export($data));
        }
    }

    private function encode(array $data): string
    {
        try {
            return Json::encode($data, Json::PRETTY);
        } catch (Throwable $e) {
            Assert::fail($e->getMessage() . PHP_EOL . var_export($data));
        }
    }
}
