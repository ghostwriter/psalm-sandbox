# psalm-plugin-tester

[![Compliance](https://github.com/ghostwriter/psalm-plugin-tester/actions/workflows/compliance.yml/badge.svg)](https://github.com/ghostwriter/psalm-plugin-tester/actions/workflows/compliance.yml)
[![Supported PHP Version](https://badgen.net/packagist/php/ghostwriter/psalm-plugin-tester?color=8892bf)](https://www.php.net/supported-versions)
[![Mutation Coverage](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fghostwriter%2Fwip%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/ghostwriter/psalm-plugin-tester/main)
[![Code Coverage](https://codecov.io/gh/ghostwriter/psalm-plugin-tester/branch/main/graph/badge.svg?token=UPDATE_TOKEN)](https://codecov.io/gh/ghostwriter/psalm-plugin-tester)
[![Type Coverage](https://shepherd.dev/github/ghostwriter/psalm-plugin-tester/coverage.svg)](https://shepherd.dev/github/ghostwriter/psalm-plugin-tester)
[![Latest Version on Packagist](https://badgen.net/packagist/v/ghostwriter/psalm-plugin-tester)](https://packagist.org/packages/ghostwriter/psalm-plugin-tester)
[![Downloads](https://badgen.net/packagist/dt/ghostwriter/psalm-plugin-tester?color=blue)](https://packagist.org/packages/ghostwriter/psalm-plugin-tester)

work in progress

> **Warning**
>
> This project is not finished yet, work in progress.


## Installation

You can install the package via composer:

``` bash
composer require ghostwriter/psalm-plugin-tester
```

## Usage

- create a `tests/fixtures/` directory.
- create a test fixture `psalm-runs-without-any-errors` directory in `tests/fixtures/`.
- create an `expectation.json` in the `psalm-runs-without-any-errors` directory.
- add a few `.php` files that you want the plugin to test, in the `psalm-runs-without-any-errors` directory.
- add your expectation in JSON format.

>    // No errors `expectation.json`
>    ```json
>    {}
>    ```

>    // Has Errors `expectation.json`
>    ```json
>    {
>        "errors": [
>            "MissingConstructor" : [
>                "path/to/file.php"
>                "path/to/file2.php"
>            ],
>            "PossiblyNullReference" : [
>                "path/to/file.php"
>            ]
>        ]
>    }
>    ```
    

```php
<?php

declare(strict_types=1);

namespace Ghostwriter\ExamplePsalmPlugin
{
    use Psalm\Plugin\EventHandler\AfterAnalysisInterface;
    use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;
    use Psalm\Plugin\PluginEntryPointInterface;
    use Psalm\Plugin\RegistrationInterface;
    use SimpleXMLElement;
    
    final class ExampleHooks implements AfterAnalysisInterface
    {
        public static function afterAnalysis(AfterAnalysisEvent $event): void
        {
            var_dump($event->getIssues());
            die;
        }
    }
    
    final class ExamplePlugin implements PluginEntryPointInterface
    {
        public function __invoke(RegistrationInterface $registration, SimpleXMLElement|null $config = null): void
        {
            class_exists(ExampleHooks::class);
            $registration->registerHooksFromClass(ExampleHooks::class);
        }
    }
}

namespace Ghostwriter\ExamplePsalmPlugin\Tests
{
    use Generator;
    use Ghostwriter\ExamplePsalmPlugin\ExamplePlugin;
    use Ghostwriter\PsalmPluginTester\PluginTester;
    use PHPUnit\Framework\TestCase;
    
    final class ExamplePluginTest extends TestCase
    {
        private PluginTester $pluginTester;
        protected function setUp(): void
        {
            $this->pluginTester = new PluginTester(ExamplePlugin::class);
        }

        public static function fixtureDataProvider(): Generator
        {
            yield from $this->psalmPluginTester->fixtures(__DIR__ . '/../tests/fixtures/');
        }

        /** @dataProvider fixtureDataProvider
        public function testPlugin(string $path): void
        {
            $this->pluginTester->test(ExamplePlugin::class, $path);
        }
    }
}
```
- run phpunit

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG.md](./CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security related issues, please email `nathanael.esayeas@protonmail.com` instead of using the issue tracker.

## Support

[[`Become a GitHub Sponsor`](https://github.com/sponsors/ghostwriter)]

## Credits

- [Nathanael Esayeas](https://github.com/ghostwriter)
- [All Contributors](https://github.com/ghostwriter/psalm-plugin-tester/contributors)

## License

The BSD-3-Clause. Please see [License File](./LICENSE) for more information.
