<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Ghostwriter\Json\Json;
use PHPUnit\Framework\Assert;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Throwable;

final class PluginTestResult
{
    private readonly array $errorOutput;

    public function __construct(
        private readonly Fixture $fixture,
        private readonly Analyzer $analyzer,
        private readonly string $pluginClass,
        private readonly string $phpVersion
    ) {
        $this->errorOutput =
            [
                'errors' => array_map(
                    static fn(IssueData $issueData): array =>
                    [
                        'file' => $issueData->file_name,
                        'message' => $issueData->message,
                        'severity' => $issueData->severity,
                        'type' => $issueData->type,
                    ],
                    array_merge(
                        ...array_values(
                            $analyzer->getCodebase()->file_reference_provider->getExistingIssues()
                        )
                    ),
                ),
            ];

        $expected = null;
        $actual = $this->errorOutput;
        $codebase = $analyzer->getCodebase();
        $expectations = [
            'pluginClass' => $pluginClass,
            'php' => $phpVersion,
            // 'errorCount' => IssueBuffer::getErrorCount(),
            'expected' => $actual,
            'actual' => $actual,
            'plugin' => [
                $pluginClass => [
                    'phpVersion' => $phpVersion,
                ],
            ],
            'TypeInferenceSummary' => $codebase->analyzer->getTypeInferenceSummary($codebase)
        ];

        $root = $this->fixture->getSourceDirectory();

        $expectationsFile = $root . '/expectations.json';
        if (file_exists($expectationsFile)) {
            $contents = file_get_contents($expectationsFile);
            if ($contents === false) {
                Assert::fail(
                    sprintf(
                        'Could not read expectations file: %s',
                        $expectationsFile
                    )
                );
            }

            $expected = Json::decode(trim($contents))['expected'] ?? null;
            if ($expected !== null) {
                $expectations['expected'] = $expected;
            }
        } else {
            Assert::assertGreaterThan(
                0,
                file_put_contents($expectationsFile, Json::encode($expectations, Json::PRETTY) . PHP_EOL),
                sprintf('Could not write expectations file: %s', $expectationsFile)
            );
        }

        Assert::assertSame(
            $expectations['expected'],
            $expectations['actual'],
            sprintf(
                'Could not match contents of the expectations file: %s',
                $expectationsFile
            )
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
