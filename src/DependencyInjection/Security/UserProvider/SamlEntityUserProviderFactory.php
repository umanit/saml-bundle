<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection\Security\UserProvider;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SamlEntityUserProviderFactory implements UserProviderFactoryInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $container
            ->setDefinition($id, new ChildDefinition('umanit_saml.security.user.saml_entity_user_provider'))
            ->addArgument($config['class'])
            ->addArgument($config['property'])
            ->addArgument($config['manager_name'])
            ->addArgument($config['default_roles'])
            ->addArgument($config['restrictions'])
            ->addArgument($config['roles_mapping'])
            ->addArgument($config['case_insensitive'])
        ;
    }

    public function getKey(): string
    {
        return 'saml_entity';
    }

    /**
     * @param NodeDefinition&ParentNodeDefinitionInterface $builder
     */
    public function addConfiguration(NodeDefinition $builder): void
    {
        // @formatter:off
        $builder
            ->children()
                ->scalarNode('class')
                    ->isRequired()
                    ->info('The full entity class name of your user class.')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('default_roles')
                    ->prototype('scalar')->end()
                    ->defaultValue(['ROLE_USER'])
                ->end()
                ->scalarNode('property')->defaultNull()->end()
                ->scalarNode('manager_name')->defaultNull()->end()
                ->scalarNode('case_insensitive')->defaultValue(false)->end()
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
