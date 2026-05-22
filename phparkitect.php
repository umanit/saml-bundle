<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsEnum;
use Arkitect\Expression\ForClasses\MatchOneOfTheseNames;
use Arkitect\Expression\ForClasses\NotHaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Umanit\DevBundle\Arkitect\Expression\ForClasses\NotAbuseFinalUsage;
use Umanit\DevBundle\Arkitect\Expression\ForClasses\NotUseGenericException;

const BUNDLE_NAME = 'App';

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__ . '/src');
    $rules = [];

    /* BEGIN - Uniformisation des noms de classes */
    $rules[] = Rule
        ::allClasses()
        ->except('*Trait')
        ->that(new ResideInOneOfTheseNamespaces(BUNDLE_NAME . '\Controller'))
        ->should(new MatchOneOfTheseNames(['*Controller', '*Action']))
        ->because('we want uniform controllers and actions naming')
    ;

    $rules[] = Rule
        ::allClasses()
        ->that(new ResideInOneOfTheseNamespaces(BUNDLE_NAME . '\Command'))
        ->should(new HaveNameMatching('*Command'))
        ->because('we want uniform commands naming')
    ;

    $rules[] = Rule
        ::allClasses()
        ->that(new ResideInOneOfTheseNamespaces(BUNDLE_NAME . '\EventSubscriber'))
        ->should(new HaveNameMatching('*Subscriber'))
        ->because('we want uniform event subscribers naming')
    ;

    $rules[] = Rule
        ::allClasses()
        ->except('*Interface', '*Trait')
        ->that(new ResideInOneOfTheseNamespaces(BUNDLE_NAME . '\Form'))
        ->should(new MatchOneOfTheseNames(['*Mapper', '*Transformer', '*Type', '*TypeExtension', '*Subscriber']))
        ->because('we want uniform form classes naming')
    ;

    $rules[] = Rule
        ::allClasses()
        ->that(new IsEnum())
        ->should(new NotHaveNameMatching('*Enum'))
        ->because('we don\'t want use "Enum" suffix')
    ;
    /* END - Uniformisation des noms de classes */

    // Tous les Repository doivent avoir un nom finissant par Repository
    $rules[] = Rule
        ::allClasses()
        ->except('*Trait')
        ->that(new ResideInOneOfTheseNamespaces(BUNDLE_NAME . '\Repository'))
        ->should(new MatchOneOfTheseNames(['*Repository', '*RepositoryInterface']))
        ->because('we want uniform naming for repositories')
    ;

    // Interdiction d’utiliser directement l’exception \Exception
    $rules[] = Rule
        ::allClasses()
        ->that(new ResideInOneOfTheseNamespaces(BUNDLE_NAME))
        ->should(new NotUseGenericException())
        ->because('we want to force usage of SPL exceptions or custom ones')
    ;

    // Détection de l’abus de classes final
    $rules[] = Rule
        ::allClasses()
        ->that(new ResideInOneOfTheseNamespaces(BUNDLE_NAME))
        ->should(new NotAbuseFinalUsage())
        ->because('we want avoid final classes which reduce extensibility')
    ;

    $config->add($classSet, ...$rules);
};
