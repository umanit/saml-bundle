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
        $this->setConfigAsParameters($container, $config, $rootName);
    }

    /**
     * Ajoute les clés de la config en parameters
     *
     * @param ContainerBuilder $container
     * @param array            $params
     * @param string           $parent
     */
    private function setConfigAsParameters(ContainerBuilder $container, array $params, string $parent): void
    {
        foreach ($params as $key => $value) {
            $name = $parent . '.' . $key;
            $container->setParameter($name, $value);

            if (\is_array($value) && !$this->checkIntKeysOnly($value)) {
                $this->setConfigAsParameters($container, $value, $name);
            }
        }
    }

    /**
     * Vérifie que toutes les clés sont des entiers
     *
     * @param array $param Le tableau pour lequel il faut vérifier les clés
     *
     * @return bool true si toutes les clés sont des entiers, false sinon
     *
     * @example <code>
     * $test = array(1=>1, 2=>2, 4=>4);
     * _checkIntKeysOnly($test); // => bool(true)
     *
     * $test = array(1=>1, 2=>2, 'b'=>4, 10=>'aze');
     * _checkIntKeysOnly($test); // => bool(false)
     * </code>
     */
    private function checkIntKeysOnly(array $param): bool
    {
        $keys = array_keys($param);
        $isInt = \is_int(reset($keys));

        // Tant les clés sont des entiers et qu'il y a des éléments suivants
        while ($isInt && $key = next($keys)) {
            $isInt &= \is_int($key);
        }

        return $isInt === true;
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
