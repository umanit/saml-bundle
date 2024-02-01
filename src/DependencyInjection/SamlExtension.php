<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SamlExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $rootName = Configuration::NAME;
        $container->setParameter($rootName, $config);
        $this->setConfigAsParameters($container, $config, $rootName);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
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
}
