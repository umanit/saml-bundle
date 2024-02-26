# Code quality tools

## PHPUnit

PHPUnit is a programmer-oriented testing framework for PHP.

Use the following command to run the tests:

```bash
 symfony php vendor/bin/phpunit -c phpunit.xml.dist --group unit --log-junit phpunit-report.xml 
```

```bash
 symfony php vendor/bin/phpunit  -c phpunit-ci.xml --coverage-html build
```

## PHPStan

PHPStan is a static analysis tool that helps you find bugs in your code without actually running it.

Use the following command to run PHPStan:

```bash
 symfony php vendor/bin/phpstan analyse -c phpstan.neon --level 7 src/ tests/
```

## PHPCS

PHPCS is a tool to check coding standard violations.

Use the following command to run PHPCS:

```bash
 symfony php vendor/bin/phpcs --standard=PSR12 src/ tests/
```

## PHPCbf

PHPCbf is a tool to automatically fix coding standard violations.

Use the following command to run PHPCbf:

```bash
 symfony php vendor/bin/phpcbf --standard=PSR12 src/ tests/
```
