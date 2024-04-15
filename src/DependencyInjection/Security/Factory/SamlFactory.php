<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SamlFactory extends AbstractFactory
{
    public const PRIORITY = -10;

    public function __construct()
    {
        // Gestion du relay State
        $this->addOption(
            'success_handler',
            'umanit_saml.security.http.authentication.saml_authentication_success_handler'
        );
        $this->addOption(
            'saml_restrictions',
            []
        );
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    public function getKey(): string
    {
        return 'saml';
    }

    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ): string|array {
        $authenticatorId = 'security.authenticator.saml.' . $firewallName;
        $authenticator = (new ChildDefinition('umanit_saml.security.http.authenticator.saml_authenticator'))
            ->replaceArgument(1, new Reference($userProviderId))
            ->replaceArgument(
                2,
                new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config))
            )
            ->replaceArgument(
                3,
                new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config))
            )
            ->replaceArgument(4, array_intersect_key($config, $this->options))
        ;

        $container->setDefinition($authenticatorId, $authenticator);

        return $authenticatorId;
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        parent::addConfiguration($node);

        // @formatter:off
        /** @phpstan-ignore-next-line */
        $node
            ->children()
                ->arrayNode('saml_restrictions')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('attribute_name')->end()
                            ->scalarNode('type')->end()
                            ->scalarNode('needed')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
    }
}
