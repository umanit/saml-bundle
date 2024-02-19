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

    public function getByProvider(string $provider): ?array
    {
        return $this->config['providers'][$provider] ?? null;
    }

    public function getProviderNames(): array
    {
        return array_keys($this->config['providers']);
    }
}
