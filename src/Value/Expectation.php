<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Value;

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
        return sprintf(
            '%s: %s | %s',
            $this->file,
            $this->type,
            $this->message,
        );
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
