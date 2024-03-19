<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

class ConfigurationService implements ConfigurationServiceInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(protected array $config)
    {
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
        /** @var array<int, string>  $names */
        $names = array_keys($this->config['providers']);

        return $names;
    }
}
