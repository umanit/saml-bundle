<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

interface ConfigurationServiceInterface
{
    /**
     * @param string $provider
     *
     * @return array<string, mixed>
     */
    public function getByProvider(string $provider): array;

    /**
     * @return array<int, string>
     */
    public function getProviderNames(): array;
}
