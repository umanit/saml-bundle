<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

class ConfigurationService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getByProvider(string $provider): ?array
    {
        return $this->config[$provider] ?? null;
    }
}
