<?php

declare(strict_types=1);

namespace Ghostwriter\ExamplePsalmPlugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use ReflectionClass;
use SimpleXMLElement;

final class ExamplePlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, SimpleXMLElement|null $config = null): void
    {
        require_once (new ReflectionClass(ExampleHooks::class))->getFileName();

        $registration->registerHooksFromClass(ExampleHooks::class);
    }
}
