<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use Exception;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\EntitiesDescriptor;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\Metadata;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IdpMetadataService implements IdpMetadataServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected HttpClientInterface $client,
        protected CacheInterface $cache,
        protected LoggerInterface $logger
    ) {
    }

    public function getXml(string $metadata, array $idpConfig): ?string
    {
        if (!filter_var($metadata, FILTER_VALIDATE_URL)) {
            if (file_exists($metadata) && is_readable($metadata)) {
                $this->logger->debug('Getting metadata from file');

                return file_get_contents($metadata);
            }

            $this->logger->debug('Getting metadata from string');

            return $metadata;
        }

        $tokenId = $this->getTokenId($idpConfig);
        $metadataTtl = (int) floor($idpConfig['metadata_cache_duration'] ?? self::DEFAULT_METADATA_CACHE_DURATION);

        if ($this->cache->hasItem($tokenId)) {
            $this->logger->debug('Getting metadata from cache', [
                'tokenId' => $tokenId,
            ]);

            return $this->cache->getItem($tokenId)->get();
        }

        return $this->cache->get($tokenId, function (CacheItem $item) use ($metadata, $metadataTtl): string {
            $item->expiresAfter($metadataTtl);

            $this->logger->debug('Getting metadata from url and save it to cache', [
                'metadata'    => $metadata,
                'metadataTtl' => $metadataTtl,
            ]);

            $xml = $this->client->request('GET', $metadata)->getContent();

            $item->set($xml);

            return $xml;
        });
    }

    /**
     * @throws Exception
     */
    public function getEntityDescriptor(string $provider): EntityDescriptor
    {
        $this->logger->debug('Getting entity descriptor for provider {provider}', ['provider' => $provider]);

        $config = $this->configurationService->getByProvider($provider);
        $idpConfig = $config['idp'];
        $idpEntityId = $idpConfig['entity_id'] ?? null;
        $metadata = $idpConfig['metadata'] ?? null;

        if (null === $metadata) {
            throw new RuntimeException('No metadata found');
        }

        return $this->getEntityDescriptorFromXml($this->getXml($metadata, $idpConfig), $idpEntityId);
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
     * @throws InvalidArgumentException
     */
    public function clearCache(string $provider): void
    {
        $this->logger->debug('Clearing cache for provider {provider}', ['provider' => $provider]);

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
