<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection\Security\UserProvider;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SamlScopedUserProviderFactory implements UserProviderFactoryInterface
{
    /**
     * @param ContainerBuilder     $container
     * @param string               $id
     * @param array<string, mixed> $config
     *
     * @return void
     */
    public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $providers = $config['providers'] ?? [];

        foreach ($providers as $samlProvider => $userProvider) {
            $providers[$samlProvider] = new Reference($userProvider);
        }

        $container
            ->setDefinition($id, new ChildDefinition('umanit_saml.security.user.saml_scoped_user_provider'))
            ->addArgument($providers)
            ->addArgument(new Reference('umanit_saml.service.configuration_service'))
        ;
    }

    public function getKey(): string
    {
        return 'saml_scoped';
    }

    /**
     * @param NodeDefinition&ParentNodeDefinitionInterface $builder
     */
    public function addConfiguration(NodeDefinition $builder): void
    {
        // @formatter:off
        $builder
            ->children()
                ->arrayNode('providers')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;
        // @formatter:on
    }
}
