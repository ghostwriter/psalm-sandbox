<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Ghostwriter\Json\Json;
use JsonSerializable;
use Stringable;

final class Expectation implements JsonSerializable, Stringable
{
    public function __construct(
        private readonly string $file,
        private readonly string $message,
        private readonly string $severity,
        private readonly string $type,
    ) {
    }

    public function __toString(): string
    {
        return Json::encode($this->jsonSerialize());
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string[]
     *
     * @psalm-return array{file: string, message: string, severity: string, type: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'file' => $this->file,
            'message' => $this->message,
            'severity' => $this->severity,
            'type' => $this->type,
        ];
    }
}
