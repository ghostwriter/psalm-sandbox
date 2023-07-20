<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path;

use Stringable;

interface PathInterface extends Stringable
{
    public function exists(): bool;
}
