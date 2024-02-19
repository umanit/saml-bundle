<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use Exception;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\EntitiesDescriptor;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\Metadata;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Cache\CacheItem;
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
        $idpEntityId = $idpConfig['entity_id'] ?? null;
        $metadata = $idpConfig['metadata'] ?? null;

        if (null === $metadata) {
            throw new RuntimeException('No metadata found');
        }

        if (filter_var($metadata, FILTER_VALIDATE_URL) !== false) {
            return $this->getEntityDescriptorFromUrl($metadata, $idpConfig);
        }

        return $this->getEntityDescriptorFromXml($metadata, $idpEntityId);
    }

    /**
     * @param string      $xml
     * @param string|null $entityId
     *
     * @return EntityDescriptor
     * @throws Exception
     */
    protected function getEntityDescriptorFromXml(string $xml, ?string $entityId): EntityDescriptor
    {
        // If the metadata is a file, we read it
        if (file_exists($xml) && is_readable($xml)) {
            $xml = file_get_contents($xml);
        }

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

        $tokenId = $this->getTokenId($idpConfig);
        $metadataTtl = (int) floor($idpConfig['metadata_cache_duration'] ?? self::DEFAULT_METADATA_CACHE_DURATION);

        if ($this->cache->hasItem($tokenId)) {
            $xml = $this->cache->getItem($tokenId)->get();
        } else {
            $xml = $this->cache->get($tokenId, function (CacheItem $item) use ($tokenId, $url, $metadataTtl): string {
                $item->expiresAfter((int) $metadataTtl);
                $xml = $this->client->request('GET', $url)->getContent();

                $item->set($xml);
                $item->tag($tokenId);

                return $xml;
            });
        }

        return $this->getEntityDescriptorFromXml($xml, ($idpConfig['entity_id'] ?? null));
    }

    public function clearCache(string $provider): void
    {
        $config = $this->configurationService->getByProvider($provider);
        $idpConfig = $config['idp'];

        $tokenId = $this->getTokenId($idpConfig);

        $this->cache->delete($tokenId);
    }

    protected function getTokenId(array $idpConfig = []): string
    {
        $str = $idpConfig['metadata'] ?? '';

        if (empty($str)) {
            throw new RuntimeException('Impossible to generate token ID, for metadata');
        }

        return sha1($str);
    }
}
