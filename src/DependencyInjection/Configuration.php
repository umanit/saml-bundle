<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Umanit\SamlBundle\Service\IdpMetadataService;
use Umanit\SamlBundle\Service\IdpMetadataServiceInterface;

class Configuration implements ConfigurationInterface
{
    public const NAME = 'umanit_saml';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::NAME);

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('certificat_path')->isRequired()->defaultValue('certs')->end()
                ->arrayNode('providers')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('provider_name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('sp')
                                ->children()
                                    ->enumNode('type')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->values(['spinitiated', 'idpinitiated'])
                                        ->info('Type de SAML à utiliser (SP Initiated ; IdP Initiated)')
                                    ->end()
                                    ->arrayNode('assertionConsumerService')
                                        ->children()
                                            ->scalarNode('url')->isRequired()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('singleLogoutService')
                                        ->children()
                                            ->scalarNode('url')->isRequired()->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('privateKey')->isRequired()->end()
                                ->end()
                            ->end()
                            ->arrayNode('idp')
                                ->children()
                                    ->scalarNode('entity_id')->info('Entity id, by default the first of the metadata')->end()
                                    ->scalarNode('metadata')->info('Metadata URL, File or XML string')->isRequired()->end()
                                    ->scalarNode('metadata_cache_duration')
                                        ->info('Metadata cache duration in seconds')
                                        ->defaultValue(IdpMetadataServiceInterface::DEFAULT_METADATA_CACHE_DURATION)
                                        ->end()

                                    ->arrayNode('SingleSignOnService')
                                        ->children()
                                            ->scalarNode('url')->isRequired()->end()
                                            ->scalarNode('binding')->isRequired()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('singleLogoutService')
                                        ->children()
                                            ->scalarNode('url')->isRequired()->end()
                                            ->scalarNode('binding')->isRequired()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
