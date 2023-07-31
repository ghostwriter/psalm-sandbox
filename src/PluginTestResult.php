<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Ghostwriter\Json\Json;
use Ghostwriter\PsalmPluginTester\Path\Directory\Fixture;
use PHPUnit\Framework\Assert;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\IssueBuffer;
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
        set_time_limit(-1);
        // 8GiB Memory Limit
        ini_set('memory_limit', (string) (8 * 1024 * 1024 * 1024));
        // show all errors
        error_reporting(-1);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');

        // $codebase = $projectAnalyzer->getCodebase();
        //        $codebase->config->initializePlugins($projectAnalyzer);
        //        $codebase->config->visitPreloadedStubFiles($codebase);
        //        $codebase->config->visitStubFiles($codebase);
        //        $codebase->config->visitComposerAutoloadFiles($projectAnalyzer);

        //        $codebase->allow_backwards_incompatible_changes = true;
        //        $projectAnalyzer->setPhpVersion($options['php-version']);
        //        Config::getInstance()->addPluginPath($current_dir . $plugin_path);

        gc_collect_cycles();
        gc_disable();
        $projectAnalyzer->check($fixture->getPath());
        gc_enable();
        gc_collect_cycles();

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
    }

    public function assertExpectations(): self
    {
        $encode = $this->encode($this->errorOutput);

        $root = $this->fixture->getPath();

        Assert::assertSame(
            $this->fixture->getProjectRootDirectory()
                ->getExpectationsJsonFile()
                ->mapOrElse(
                    fn ($file) => $this->encode([
                        'errors' => $file->getExpectations(),
                    ]),
                    static function () use ($root, $encode): string {
                        file_put_contents($root . '/expectations.json', $encode . PHP_EOL);

                        return $encode;
                    }
                ),
            $encode,
            sprintf('Expected output does not match expectations file: %s/expectations.json', $root)
        );

        return $this;
    }

    public function getFixture(): Fixture
    {
        return $this->fixture;
    }

    public function getPlugin(): string
    {
        return $this->plugin;
    }

    public function getPluginClass(): string
    {
        return $this->pluginClass;
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
