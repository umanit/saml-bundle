<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SamlFactory extends AbstractFactory
{
    public const PRIORITY = -10;

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
            ->replaceArgument(2, new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config)))
            ->replaceArgument(3, new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config)))
            ->replaceArgument(4, array_intersect_key($config, $this->options))
        ;

        $container->setDefinition($authenticatorId, $authenticator);

        return $authenticatorId;
    }
}
