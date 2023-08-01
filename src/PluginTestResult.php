<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Ghostwriter\Json\Json;
use PHPUnit\Framework\Assert;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\PluginFileExtensionsInterface;
use Psalm\Plugin\PluginInterface;
use Throwable;

final class PluginTestResult
{
    private array $errorOutput;

    /**
     * @param class-string<PluginEntryPointInterface|PluginFileExtensionsInterface|PluginInterface> $pluginClass
     */
    public function __construct(
        private readonly string $pluginClass,
        private readonly string $plugin,
        private readonly Fixture $fixture,
        private readonly ProjectAnalyzer $projectAnalyzer,
    ) {
        $this->errorOutput =
            [
                'errors' => array_map(
                    static fn (IssueData $issuesData): array =>
                    [
                        'file' => $issuesData->file_name,
                        'message' => $issuesData->message,
                        'severity' => $issuesData->severity,
                        'type' => $issuesData->type,
                    ],
                    array_merge(
                        ...array_values(
                            $projectAnalyzer->getCodebase()->file_reference_provider->getExistingIssues()
                        )
                    ),
                ),
            ];

        $actual = $this->encode($this->errorOutput);

        $root = $this->fixture->getPath();

        $expectationsFile = $root . '/expectations.json';
        if (file_exists($expectationsFile)) {
            $expected = file_get_contents($expectationsFile);
            if ($expected === false) {
                Assert::fail(
                    sprintf(
                        'Could not read expectations file: %s',
                        $expectationsFile
                    )
                );
            }
        } else {
            $expected = $actual;
            Assert::assertGreaterThan(
                0,
                file_put_contents($expectationsFile, $actual . PHP_EOL),
                sprintf('Could not write expectations file: %s', $expectationsFile)
            );
        }

        Assert::assertJsonStringEqualsJsonFile(
            $expectationsFile,
            $actual,
            sprintf('Could not match contents of the expectations file: %s', $expectationsFile)
        );
    }

    public function getFixture(): Fixture
    {
        return $this->fixture;
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
