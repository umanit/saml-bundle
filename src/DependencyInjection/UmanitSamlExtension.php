<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class UmanitSamlExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('lightsaml.yaml');
        $loader->load('services.yaml');
        $loader->load('commands.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Injection de la configuration dans le service
        $container->getDefinition('umanit_saml.service.configuration_service')
            ->setArgument(0, $config);

        $rootName = Configuration::NAME;
        $container->setParameter($rootName, $config);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('monolog', [
            'channels' => ['umanit_saml'],
            'handlers' => [
                'umanit_saml'      => [
                    'type'         => 'fingers_crossed',
                    'action_level' => 'debug',
                    'handler'      => 'umanit_saml_file',
                    'channels'     => ['umanit_saml'],
                ],
                'umanit_saml_file' => [
                    'type'      => 'rotating_file',
                    'path'      => '%kernel.logs_dir%/%kernel.environment%_umanit_saml.log',
                    'level'     => 'debug',
                    'max_files' => 60,
                    'channels'  => ['umanit_saml'],
                ],
            ],
        ]);
    }
}
