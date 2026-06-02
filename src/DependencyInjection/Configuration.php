<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection;

use LightSaml\SamlConstants;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Umanit\SamlBundle\Enums\Encryption;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Enums\SamlEncryptionSignature;
use Umanit\SamlBundle\Service\MetadataServiceInterface;

class Configuration implements ConfigurationInterface
{
    public const string NAME = 'umanit_saml';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::NAME);

        $rootNode = $treeBuilder->getRootNode();

        // @formatter:off
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('certificat_path')->defaultValue('%kernel.project_dir%/certs')->end()
                ->arrayNode('twig_templates')
                    ->defaultValue([
                        'redirect' => '@UmanitSaml/redirect.html.twig',
                    ])
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('providers')
                    ->defaultValue([])
                    ->useAttributeAsKey('provider_name')
                    ->arrayPrototype()
                        ->children()
                            ->booleanNode('enabled')->defaultTrue()->info('Activer ou non le provider')->end()
                            ->booleanNode('strict')->defaultTrue()->info('Activer ou non le mode strict')->end()
                            ->booleanNode('enable_slo')->defaultTrue()->info('Activer le SLO au logout')->end()
                            ->arrayNode('tags')
                                ->prototype('scalar')->end()
                            ->end()
                            ->enumNode('type')
                                ->values(Mode::cases())
                                ->defaultValue(Mode::SP_INITIATED)
                                ->info('Type de SAML à utiliser (SP Initiated ; IdP Initiated)')
                            ->end()
                            ->arrayNode('sp')
                                ->children()
                                    ->scalarNode('entity_id')->defaultValue('{{host}}')->info('Entity id')->end()
                                    ->scalarNode('metadata')->info('Metadata URL, File or XML string')->end()
                                    ->scalarNode('metadata_cache_duration')
                                        ->info('Metadata cache duration in seconds')
                                        ->defaultValue(MetadataServiceInterface::DEFAULT_METADATA_CACHE_DURATION)
                                        ->end()
                                    ->scalarNode('name_id_format')->info('NameIDFormat')
                                        ->defaultValue(SamlConstants::NAME_ID_FORMAT_PERSISTENT)
                                        ->validate()
                                            ->ifTrue(
                                                static fn(string $v): bool => !SamlConstants::isNameIdFormatValid($v),
                                            )
                                            ->thenInvalid('Invalid NameIDFormat %s')->end()
                                        ->end()
                                    ->arrayNode('acs')->info('Assertion Consumer Service')->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('url')->end()
                                            ->scalarNode('route')->defaultValue('umanit_saml_acs')->end()
                                            ->scalarNode('binding')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('slo')->info('Single Logout Service')->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('url')->end()
                                            ->scalarNode('route')->defaultValue('umanit_saml_slo')->end()
                                            ->scalarNode('binding')->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('x509cert')->info('X509 Certificat')->end()
                                    ->arrayNode('private_key')->info('Private Key')
                                        ->children()
                                            ->scalarNode('path')->info('Path to private Key')->end()
                                            ->enumNode('encryption')
                                                ->values(Encryption::cases())
                                                ->defaultValue(Encryption::RSA_SHA256)
                                                ->info('Type de chiffrement')
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('private_key_passphrase')->info('Private Key Passphrase')->end()
                                    ->enumNode('saml_algorithm_signature')
                                        ->values(SamlEncryptionSignature::cases())
                                        ->defaultValue(SamlEncryptionSignature::SHA256)
                                        ->info('Type de chiffrement')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('idp')
                                ->children()
                                    ->scalarNode('entity_id')
                                        ->defaultValue('{{host}}')
                                        ->info('Entity id, by default the first of the metadata')
                                    ->end()
                                    ->booleanNode('disable_ssl_verification')
                                        ->defaultFalse()
                                        ->info(
                                            'Disables SSL certificate verification : useful for connecting to'
                                            . ' endpoints with self-signed certificates. Not recommended in production',
                                        )
                                    ->end()
                                    ->scalarNode('metadata')->info('Metadata URL, File or XML string')->end()
                                    ->scalarNode('metadata_cache_duration')
                                        ->info('Metadata cache duration in seconds')
                                        ->defaultValue(MetadataServiceInterface::DEFAULT_METADATA_CACHE_DURATION)
                                        ->end()
                                    ->scalarNode('name_id_format')->info('NameIDFormat')
                                        ->defaultValue(SamlConstants::NAME_ID_FORMAT_PERSISTENT)
                                        ->validate()
                                            ->ifTrue(
                                                static fn(string $v): bool => !SamlConstants::isNameIdFormatValid($v),
                                            )
                                            ->thenInvalid('Invalid NameIDFormat %s')->end()
                                        ->end()
                                    ->arrayNode('sso')->info('Single Sign On Service')->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('url')->end()
                                            ->scalarNode('route')->defaultValue('umanit_saml_sso')->end()
                                            ->scalarNode('binding')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('slo')->info('Single Logout Service')->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('url')->end()
                                            ->scalarNode('route')->defaultValue('umanit_saml_slo')->end()
                                            ->scalarNode('binding')->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('x509cert')->info('X509 Certificat')->end()
                                    ->arrayNode('private_key')->info('Private Key')
                                        ->children()
                                            ->scalarNode('path')->info('Path to private Key')->end()
                                            ->enumNode('encryption')
                                                ->values(Encryption::cases())
                                                ->defaultValue(Encryption::RSA_SHA256)
                                                ->info('Type de chiffrement')
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('private_key_passphrase')->info('Private Key Passphrase')->end()
                                    ->enumNode('saml_algorithm_signature')
                                        ->values(SamlEncryptionSignature::cases())
                                        ->defaultValue(SamlEncryptionSignature::SHA256)
                                        ->info('Type de chiffrement')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                    ->ifTrue(static function (array $providers): bool {
                        foreach ($providers as $data) {
                            $isSpInitiated = Mode::SP_INITIATED === $data['type'];

                            if ($isSpInitiated && empty($data['sp']['private_key'])) {
                                return true;
                            }

                            if (!$isSpInitiated && empty($data['idp']['private_key'])) {
                                return true;
                            }
                        }

                        return false;
                    })
            ->thenInvalid('Provider %s must have private_key')
            ->end()
            ->end();
        // @formatter:on

        return $treeBuilder;
    }
}
