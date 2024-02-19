<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

class ConfigurationService implements ConfigurationServiceInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getByProvider(string $provider): array
    {
        $config = $this->config['providers'][$provider] ?? null;

        if (empty($config)) {
            throw new \RuntimeException(sprintf('Provider "%s" not found', $provider));
        }

        return $config;
    }

    public function getProviderNames(): array
    {
        return array_keys($this->config['providers']);
    }
}
