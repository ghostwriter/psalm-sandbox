<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\File;

use Ghostwriter\Json\Json;
use Ghostwriter\PsalmPluginTester\Value\Expectation;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Throwable;

final class ExpectationsJsonFile implements FileInterface
{
    use FileTrait;

    public function getExpectations(): array
    {
        $expectations = $this->read();

        return array_map(
            static fn (
                array $expectation
            ): Expectation => new Expectation(
                $expectation['file'],
                $expectation['message'],
                $expectation['severity'],
                $expectation['type']
            ),
            $expectations['errors'] ?? []
        );
    }

    /**
     * @return array{'errors':array{'file':string,'message':string,'severity':string,'type':string}}
     */
    private function read(): array
    {
        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not read expectation file: "%s"', $this->path));
        }

        try {
            return Json::decode($contents);
        } catch (Throwable $exception) {
            Assert::fail(
                sprintf(
                    'Could not decode expectation file: "%s"; %s',
                    $this->path,
                    $exception->getMessage()
                )
            );
        }
    }
}
