<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Umanit\SamlBundle\Enums\Mode;
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
                            ->enumNode('type')
                                ->values(Mode::cases())
                                ->defaultValue(Mode::SP_INITIATED)
                                ->info('Type de SAML à utiliser (SP Initiated ; IdP Initiated)')
                            ->end()
                            ->arrayNode('sp')
                                ->children()
                                    ->scalarNode('entity_id')->info('Entity id')->end()
                                    ->arrayNode('acs')->info('Assertion Consumer Service')
                                        ->children()
                                            ->scalarNode('url')->isRequired()->end()
                                            ->scalarNode('binding')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('slo')->info('Single Logout Service')
                                        ->children()
                                            ->scalarNode('url')->isRequired()->end()
                                            ->scalarNode('binding')->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('x509cert')->isRequired()->info('X509 Certificat')->end()
                                    ->scalarNode('private_key')->isRequired()->info('Private Key')->end()
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
                                    ->arrayNode('sso')->info('Single Sign On Service')
                                        ->children()
                                            ->scalarNode('url')->end()
                                            ->scalarNode('binding')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('slo')->info('Single Logout Service')
                                        ->children()
                                            ->scalarNode('url')->end()
                                            ->scalarNode('binding')->end()
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
