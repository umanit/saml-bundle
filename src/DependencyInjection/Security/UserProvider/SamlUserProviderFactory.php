<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection\Security\UserProvider;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlUserProviderFactory implements UserProviderFactoryInterface
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
        $container
            ->setDefinition($id, new ChildDefinition('umanit_saml.security.user.saml_user_provider'))
            ->addArgument($config['user_class'])
            ->addArgument($config['default_roles'])
            ->addArgument($config['restrictions'])
            ->addArgument($config['roles_mapping'])
        ;
    }

    public function getKey(): string
    {
        return 'saml';
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
                ->scalarNode('user_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(static fn ($value) => !is_a($value, UserInterface::class, true))
                        ->thenInvalid('You should provide user class implementing ' . UserInterface::class . ' interface.')
                    ->end()
                ->end()
                ->arrayNode('default_roles')
                    ->prototype('scalar')->end()
                    ->defaultValue(['ROLE_USER'])
                ->end()
                ->arrayNode('restrictions')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('attribute_name')->end()
                            ->scalarNode('type')->end()
                            ->scalarNode('needed')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('roles_mapping')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('attribute_name')->end()
                            ->scalarNode('type')->end()
                            ->scalarNode('needed')->end()
                            ->scalarNode('role')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
    }
}
