<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

interface ConfigurationServiceInterface
{
    public function getCertificatePath(): string;

    /**
     * @param string $provider
     *
     * @return array<string, mixed>
     */
    public function getByProvider(string $provider): array;

    /**
     * @param array<string> $tags
     *
     * @return array<int, string>
     */
    public function getProviderNames(array $tags): array;

    public function getNameIdFormat(string $provider): string;

    public function getRedirectTemplate(): string;
}
