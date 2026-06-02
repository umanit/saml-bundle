<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use Umanit\SamlBundle\Exception\ProviderDisabledException;
use Umanit\SamlBundle\Exception\ProviderNotFoundException;

interface ConfigurationServiceInterface
{
    public function getCertificatePath(): string;

    /**
     * @param string $provider
     *
     * @return array<string, mixed>
     *
     * @throws ProviderNotFoundException si le provider n’existe pas
     * @throws ProviderDisabledException si le provider est désactivé
     */
    public function getByProvider(string $provider): array;

    /**
     * @param array<string> $tags
     *
     * @return array<int, string>
     */
    public function getProviderNames(array $tags): array;

    /**
     * @throws ProviderNotFoundException si le provider n’existe pas
     * @throws ProviderDisabledException si le provider est désactivé
     */
    public function getNameIdFormat(string $provider): string;

    public function getRedirectTemplate(): string;
}
