# Psalm Sandbox

[![Automation](https://github.com/ghostwriter/psalm-sandbox/actions/workflows/automation.yml/badge.svg)](https://github.com/ghostwriter/psalm-sandbox/actions/workflows/automation.yml)
[![PHP Version](https://badgen.net/packagist/php/ghostwriter/psalm-sandbox?color=777BB4)](https://www.php.net/supported-versions)
[![Packagist Downloads](https://badgen.net/packagist/dt/ghostwriter/psalm-sandbox?color=F28D1A)](https://packagist.org/packages/ghostwriter/psalm-sandbox)
[![PayPal](https://img.shields.io/badge/paypal-@codepoet-0079C1?logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI%2BPHBhdGggZD0iTTE5LjcxNSA2LjEzM2MuMjQ5LTEuODY2IDAtMy4xMS0uOTk5LTQuMjY2QzE3LjYzNC42MjIgMTUuNzIxIDAgMTMuMzA3IDBINi4yMzVjLS40MTggMC0uOTE2LjQ0NC0xIC44ODlMMi4zMjMgMjAuNjIyYzAgLjM1Ni4yNS44LjY2NS44aDQuMzI4bC0uMjUgMS45NTZjLS4wODQuMzU1LjE2Ni42MjIuNDk4LjYyMmgzLjY2M2MuNDE3IDAgLjgzMi0uMjY3LjkxNS0uNzExdi0uMjY3bC43NDktNC42MjJ2LS4xNzhjLjA4My0uNDQ0LjUtLjguOTE1LS44aC41YzMuNTc4IDAgNi4zMjUtMS41MSA3LjE1Ni01Ljk1NS40MTgtMS44NjcuMjUyLTMuMzc4LS43NDctNC40NDUtLjI1LS4zNTUtLjY2Ni0uNjIyLTEtLjg4OSIgZmlsbD0iIzAwOWNkZSIvPjxwYXRoIGQ9Ik0xOS43MTUgNi4xMzNjLjI0OS0xLjg2NiAwLTMuMTEtLjk5OS00LjI2NkMxNy42MzQuNjIyIDE1LjcyMSAwIDEzLjMwNyAwSDYuMjM1Yy0uNDE4IDAtLjkxNi40NDQtMSAuODg5TDIuMzIzIDIwLjYyMmMwIC4zNTYuMjUuOC42NjUuOGg0LjMyOGwxLjE2NC03LjM3OC0uMDgzLjI2N2MuMDg0LS41MzMuNS0uODg5Ljk5OC0uODg5aDIuMDhjNC4wNzkgMCA3LjI0MS0xLjc3OCA4LjI0LTYuNzU1LS4wODMtLjI2NyAwLS4zNTYgMC0uNTM0IiBmaWxsPSIjMDEyMTY5Ii8%2BPHBhdGggZD0iTTkuNTYzIDYuMTMzYy4wODItLjI2Ni4yNS0uNTMzLjQ5OC0uNzEuMTY2IDAgLjI1LS4wOS40MTYtLjA5aDUuNDk0Yy42NjYgMCAxLjMzLjA5IDEuODMuMTc4LjE2NiAwIC4zMzMgMCAuNDk4LjA4OS4xNjguMDg5LjMzNC4wODkuNDE4LjE3OGguMjVjLjI0OC4wODkuNDk3LjI2Ni43NDguMzU1LjI0OC0xLjg2NiAwLTMuMTEtLjk5OS00LjM1NUMxNy43MTcuNTMzIDE1LjgwNCAwIDEzLjM5IDBINi4yMzVjLS40MTggMC0uOTE2LjM1Ni0xIC44ODlMMi4zMjMgMjAuNjIyYzAgLjM1Ni4yNS44LjY2NS44aDQuMzI4bDEuMTY0LTcuMzc4IDEuMDg0LTcuOTF6IiBmaWxsPSIjMDAzMDg3Ii8%2BPC9zdmc%2B)](https://paypal.me/codepoet)
[![Sponsors via GitHub](https://img.shields.io/github/sponsors/ghostwriter?label=Sponsor+@ghostwriter/psalm-sandbox&logo=GitHub+Sponsors)](https://github.com/sponsors/ghostwriter)

work in progress

> [!WARNING]
>
> This project is not finished yet, work in progress.

## Installation

You can install the package via composer:

``` bash
composer require ghostwriter/psalm-sandbox --dev
```

## Usage

You can create a test for your plugin by extending the `AbstractPsalmSandboxTestCase` class.

eg. `tests/Unit/ExamplePluginTest.php`

```php
<?php

declare(strict_types=1);

namespace Vendor\PackageTests\Unit;

use Ghostwriter\PsalmSandbox\AbstractPsalmSandboxTestCase;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\MissingReturnType;
use Psalm\Plugin\PluginEntryPointInterface;
use Vendor\Package\ExamplePlugin;

final class ExamplePluginTest extends AbstractPsalmSandboxTestCase
{
    /**
     * @var class-string<PluginEntryPointInterface>
     */
    public const PLUGINS = [ExamplePlugin::class];

    /**
     * @var array<class-string<CodeIssue>>
     */
    public const ISSUES = [MissingReturnType::class];
}
```

You can then run your tests using PHPUnit.

``` bash
vendor/bin/phpunit
```

## Plugin Development

### Example Directory Structure

Your plugin should have the following directory structure:

``` text
composer.json
ExamplePlugin.php
src/
│   {PsalmIssueType}/
│   │   {FixIssue}.php
│   │   {ReportIssue}.php
│   │   {SuppressIssue}.php
│   MissingReturnType/
│   │   FixMissingReturnType.php
│   │   ReportMissingReturnType.php
│   │   SuppressMissingReturnType.php
tests/
│   Fixture/
│   │   {PsalmIssueType}/
│   │   │   fix-{PsalmIssueType}.php.inc
│   │   │   report-{PsalmIssueType}.php.inc
│   │   │   suppress-{PsalmIssueType}.php.inc
│   │   MissingReturnType/
│   │   │   fix-0001.php.inc
│   │   │   fix-missing-returntype.php.inc
│   │   │   report-0001.php.inc
│   │   │   report-missing-returntype.php.inc
│   │   │   suppress-0001.php.inc
│   │   │   suppress-missing-returntype.php.inc
│   Unit/
│   │   ExamplePluginTest.php
```

### Example `Fix` Test

When you run `vendor/bin/psalm --alter`, it will automatically fix the code for you.

To create a `Fix` test, you need to create a file in the `tests/Fixture/{PsalmIssueType}` directory.

The file name should be prefixed with `fix-` and any unique identifier, e.g. `fix-0001.php.inc`.

The file MUST use `--FIX--` to separate the `before` and `after` code.

The file should be structured as follows:

```php
<?php

declare(strict_types=1);

namespace Vendor\PackageTests\Fixture\MissingReturnType;

final class FixMissingReturnType
{
    public function fixMissingReturnType()
    {
    }
}
?>
--FIX--
<?php

declare(strict_types=1);

namespace Vendor\PackageTests\Fixture\MissingReturnType;

final class FixMissingReturnType
{
    public function fixMissingReturnType(): void
    {
    }
}
?>
```

### Example `Report` Test

When you run `vendor/bin/psalm`, it will report the issue in your code.

To create a `Report` test, you need to create a file in the `tests/Fixture/{PsalmIssueType}` directory.

The file name should be prefixed with `report-` and any unique identifier, e.g. `report-0001.php.inc`.

The file should be structured as follows:

```php
<?php

declare(strict_types=1);

namespace Vendor\PackageTests\Fixture\MissingReturnType;

final class ReportMissingReturnType
{
    public function reportMissingReturnType()
    {
    }
}
```

### Example `Suppress` Test

You can suppress the issue in your code by adding the `@psalm-suppress` annotation, adding suppressions to your `psalm.xml` file, or using a plugin.

To create a `Suppress` test, you need to create a file in the `tests/Fixture/{PsalmIssueType}` directory.

The file name should be prefixed with `suppress-` and any unique identifier, e.g. `suppress-0001.php.inc`.

The file should be structured as follows:

```php
<?php

declare(strict_types=1);

namespace Vendor\PackageTests\Fixture\MissingReturnType;

final class SuppressMissingReturnType
{
    /**
     * @psalm-suppress MissingReturnType
     */
    public function suppressMissingReturnType()
    {
    }
}
```

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

## Thank you

To acknowledge the efforts of those who create and maintain valuable projects for the community.

Thank you to [Bruce Weirdan](https://github.com/weirdan) for the original [`psalm/codeception-psalm-module`](https://github.com/psalm/codeception-psalm-module) that served as the starting point for my work.

Thank you to [Matt Brown](https://github.com/muglug), the creator of [`vimeo/psalm`](https://github.com/vimeo/psalm), a fantastic tool for static analysis in PHP.

Special thanks to [@orklah](https://github.com/orklah) for maintaining [`vimeo/psalm`](https://github.com/vimeo/psalm), ensuring its continuous improvement and functionality.

## Credits

- [Nathanael Esayeas](https://github.com/ghostwriter)
- [All Contributors](https://github.com/ghostwriter/psalm-sandbox/contributors)

## License

The BSD-3-Clause. Please see [License File](./LICENSE) for more information.
