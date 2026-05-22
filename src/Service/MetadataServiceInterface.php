<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Metadata\EntityDescriptor;

interface MetadataServiceInterface
{
    public const int DEFAULT_METADATA_CACHE_DURATION = 3600;

    /**
     * Générer l'EntityDescriptor pour l'application.
     */
    public function getOwnEntityDescriptor(string $provider): EntityDescriptor;

    /**
     * Récupère le XML des metadata pour générer l'EntityDescriptor.
     * Le XML est récupéré via une URL ou un fichier ou une chaîne de caractères.
     */
    public function getEntityDescriptor(string $provider): EntityDescriptor;

    /**
     * Récupère le XML des metadata.
     * Le XML est récupéré via une URL ou un fichier ou une chaîne de caractères.
     *
     * @param array<string, mixed> $config
     */
    public function getMetadataXml(array $config): string;

    /**
     * Nettoie le cache des metadata.
     */
    public function clearCache(string $provider): void;
}
