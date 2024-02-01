# Umanit Sso Bundle

## Code style
### PHPUnit
```bash
 symfony php vendor/bin/phpunit -c phpunit.xml.dist --group unit --log-junit phpunit-report.xml 
```

```bash
 symfony php vendor/bin/phpunit  --coverage-html build
```

### PHPStan
```bash
 symfony php vendor/bin/phpstan analyse -c phpstan.neon --level 7 src/ tests/
```

### PHPCS
```bash
 symfony php vendor/bin/phpcs --standard=PSR12 src/ tests/
```

### PHPCbf
```bash
 symfony php vendor/bin/phpcbf --standard=PSR12 src/ tests/
```
