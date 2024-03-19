<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Metadata\EntityDescriptor;

interface IdpMetadataServiceInterface
{
    public const DEFAULT_METADATA_CACHE_DURATION = 3600;

    public function getEntityDescriptor(string $provider): EntityDescriptor;

    public function clearCache(string $provider): void;

    /**
     * @param string $metadata
     * @param array<string, mixed>  $idpConfig
     *
     * @return string|null
     */
    public function getXml(string $metadata, array $idpConfig): ?string;
}
