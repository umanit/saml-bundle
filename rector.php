<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonyLevelSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

return static function (RectorConfig $rectorConfig): void {
    // Chemins à analyser
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    // Cache de l'analyse
    $rectorConfig->cacheClass(FileCacheStorage::class);
    $rectorConfig->cacheDirectory('./var/cache/rector');

    // Utilisation de toutes les règles jusqu'à PHP 8.2 ainsi que les règles de Symfony
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SymfonyLevelSetList::UP_TO_SYMFONY_62,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        LevelSetList::UP_TO_PHP_82,
        SymfonySetList::SYMFONY_CODE_QUALITY,
    ]);

    // Mise en place des imports "use Ma\Class\Fqcn;"
    $rectorConfig->importNames();
    $rectorConfig->ruleWithConfiguration(TypedPropertyFromAssignsRector::class, [
        TypedPropertyFromAssignsRector::INLINE_PUBLIC => true,
    ]);

    // Pas d'import des classes de PHP (\DateTime par exemple)
    $rectorConfig->importShortClasses(false);

    // Move property default from constructor to property default: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#inlineconstructordefaulttopropertyrector
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // Règles à ignorer
    $rectorConfig->skip([
        // Ne pas throw d'exception sur des "json_decode" -> Nécessite des vérifications manuelles
        JsonThrowOnErrorRector::class,
        // Ne pas utiliser les numeric_literal_separator
        AddLiteralSeparatorToNumberRector::class,
    ]);
};
