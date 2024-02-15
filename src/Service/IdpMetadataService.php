<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\EntitiesDescriptor;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\Metadata;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IdpMetadataService implements IdpMetadataServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected HttpClientInterface $client,
        protected CacheInterface $cache
    ) {
    }

    public function getEntityDescriptor(string $provider): EntityDescriptor
    {
        $config = $this->configurationService->getByProvider($provider);
        $idpConfig = $config['idp'];
        $idpEntityId = $idpConfig['entityId'] ?? null;

        if (isset($idpConfig['metadata'])) {
            return $this->getEntityDescriptorFromXml($idpConfig['metadata'], $idpEntityId);
        }

        if (isset($idpConfig['metadata_url'])) {
            return $this->getEntityDescriptorFromUrl($idpConfig['metadata_url'], $idpConfig);
        }

        throw new RuntimeException('No metadata found');
    }

    /**
     * @param string      $xml
     * @param string|null $entityId
     *
     * @return EntityDescriptor
     * @throws \Exception
     */
    protected function getEntityDescriptorFromXml(string $xml, ?string $entityId): EntityDescriptor
    {
        /** @var EntitiesDescriptor|EntityDescriptor $metadata */
        $metadata = Metadata::fromXML($xml, new DeserializationContext());

        if ($metadata instanceof EntityDescriptor) {
            return $metadata;
        }

        if (null === $entityId) {
            $metadata = current($metadata->getAllEntityDescriptors());

            if (false === $metadata) {
                throw new RuntimeException('No entity descriptor found');
            }
        }

        $metadata = $metadata->getByEntityId($entityId);

        if (null === $metadata) {
            throw new RuntimeException('No entity descriptor found');
        }

        return $metadata;
    }

    /**
     * @param string $url
     * @param array  $idpConfig
     *
     * @return EntityDescriptor
     * @throws InvalidArgumentException
     */
    protected function getEntityDescriptorFromUrl(string $url, array $idpConfig): EntityDescriptor
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Invalid URL');
        }

        $entityId = $idpConfig['entityId'] ?? null;
        $metadataTtl = $idpConfig['metadata_ttl'] ?? 3600;
        $cacheKey = sha1($url);

        if ($this->cache->hasItem($cacheKey)) {
            $xml = $this->cache->get($cacheKey)->get();
        } else {
            $this->cache->get($cacheKey, function ($item) use ($entityId, $url, $metadataTtl): string {
                $item->expiresAfter((int) $metadataTtl);
                $xml = $this->client->request('GET', $url)->getContent();
                $item->set($xml);
                $item->tag($entityId);

                return $xml;
            });
        }

        return $this->getEntityDescriptorFromXml($xml, $entityId);
    }
}
