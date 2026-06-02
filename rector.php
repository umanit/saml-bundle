<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Symfony\CodeQuality\Rector\Class_\ControllerMethodInjectionToConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\ValueObject\PhpVersion;
use Umanit\DevBundle\Rector\Rules\MonologExceptionContextKeyRector;

return RectorConfig
    ::configure()
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: true,
    )
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withPhpSets(php84: true)
    ->withAttributesSets(all: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withRules([
        MonologExceptionContextKeyRector::class,
    ])
    ->withSkip([
        __DIR__ . '/config/reference.php',
        __DIR__ . '/config/bundles.php',
        // Ne pas forcer l’utilisation de "if ($foo instanceof \Foo)" au lieu de "if (null !== $foo)"
        FlipTypeControlToUseExclusiveTypeRector::class,
        // Ne pas forcer la mise en place de l’attribut "#[Override]"
        AddOverrideAttributeToOverriddenMethodsRector::class,
        // Ne pas forcer une variable d’exception à être nommée selon son type
        CatchExceptionNameMatchingTypeRector::class,
        // Ne pas forcer à séparer les injections de paramètres et services dans les controllers
        ControllerMethodInjectionToConstructorRector::class,
        // Ne pas forcer l’utilisation des accolades autour des variables dans les strings d’interpolation
        WrapEncapsedVariableInCurlyBracesRector::class,
        // Ne pas forcer la séparation des conditions OU en early return
        ChangeOrIfContinueToMultiContinueRector::class,
        // Ne pas simplifier les patterns de regex
        SimplifyRegexPatternRector::class,
    ])
    ->withConfiguredRule(TypedPropertyFromAssignsRector::class, [
        'inline_public' => true,
    ])
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSymfonyContainerPhp(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.php')
;
