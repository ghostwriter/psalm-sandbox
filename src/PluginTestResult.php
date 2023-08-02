<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Ghostwriter\Json\Json;
use PHPUnit\Framework\Assert;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Throwable;

final class PluginTestResult
{
    private readonly array $errorOutput;

    public function __construct(
        private readonly Fixture $fixture,
        private readonly ProjectAnalyzer $projectAnalyzer,
    ) {
        $this->errorOutput =
            [
                'errors' => array_map(
                    static fn (IssueData $issueData): array =>
                    [
                        'file' => $issueData->file_name,
                        'message' => $issueData->message,
                        'severity' => $issueData->severity,
                        'type' => $issueData->type,
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

    private function encode(array $data): string
    {
        try {
            return Json::encode($data, Json::PRETTY);
        } catch (Throwable $throwable) {
            Assert::fail($throwable->getMessage() . PHP_EOL . var_export($data));
        }
    }
}
