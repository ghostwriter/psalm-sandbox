<?php

use Composer\Autoload\ClassLoader;
use Ghostwriter\Container\Container;

Container::getInstance()->get(ClassLoader::class)->add('', __DIR__);
