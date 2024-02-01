<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const NAME = 'umanit_saml';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::NAME);

        // @formatter:off
        $treeBuilder->getRootNode()
            ->useAttributeAsKey('id')
            ->arrayPrototype()
                ->children()
                    ->arrayNode('sp')
                        ->children()
                            ->booleanNode('enabled')
                                ->defaultTrue()
                            ->end()
                            ->enumNode('type')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->values(['spinitiated', 'idpinitiated'])
                                ->info('Type de SAML à utiliser (SP Initiated ; IdP Initiated)')
                            ->end()
                            ->arrayNode('assertionConsumerService')
                                ->children()
                                    ->scalarNode('url')->end()
                                    ->scalarNode('binding')->end()
                                ->end()
                            ->end()
                            ->arrayNode('singleLogoutService')
                                ->children()
                                    ->scalarNode('url')->end()
                                    ->scalarNode('binding')->end()
                                ->end()
                            ->end()
                            ->scalarNode('NameIDFormat')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('privateKey')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('idp')
                        ->children()
                            ->scalarNode('assertionConsumerService')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('singleLogoutService')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
