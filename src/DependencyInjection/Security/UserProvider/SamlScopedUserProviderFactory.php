<?php

namespace Umanit\SamlBundle\DependencyInjection\Security\UserProvider;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlScopedUserProviderFactory implements UserProviderFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $container
            ->setDefinition($id, new ChildDefinition('umanit_saml.security.user.saml_scoped_user_provider'))
            ->addArgument($config['providers'])
        ;
    }

    public function getKey(): string
    {
        return 'saml_scoped';
    }

    /**
     * @suppress PhanUndeclaredMethod
     */
    public function addConfiguration(NodeDefinition $builder): void
    {
        // @formatter:off
        /** @phpstan-ignore-next-line */
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
