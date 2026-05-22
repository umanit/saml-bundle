<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig
    ::configure()
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPreparedSets(deadCode: true, instanceOf: true)
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withPhpSets(php84: true)
    ->withAttributesSets(all: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
    ])
    ->withConfiguredRule(TypedPropertyFromAssignsRector::class, [
        'inline_public' => true,
    ])
    ->withSkip([
        // Ne pas forcer l’utilisation de "if ($foo instanceof \Foo)" au lieu de "if (null !== $foo)"
        FlipTypeControlToUseExclusiveTypeRector::class,
        // Ne pas forcer la mise en place de l’attribut "#[Override]"
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    // À incrémenter au fur et à mesure
    ->withTypeCoverageLevel(5)
    ->withTypeCoverageDocblockLevel(0)
    ->withPaths([__DIR__ . '/src'])

    // ->withSets([
    //     SymfonySetList::SYMFONY_CODE_QUALITY,
    //     SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    // ])
    // ->withSkip([
    //     JsonThrowOnErrorRector::class,
    //     AddLiteralSeparatorToNumberRector::class,
    // ])
    ;
