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
                                ->end()
                            ->end()
                            ->arrayNode('idp')
                                ->children()
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
