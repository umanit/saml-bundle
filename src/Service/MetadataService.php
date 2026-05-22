<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use Exception;
use LightSaml\Helper;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntitiesDescriptor;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\Metadata;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SingleSignOnService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use Umanit\SamlBundle\Enums\Mode;

final readonly class MetadataService implements MetadataServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected UrlGeneratorInterface $urlGenerator,
        protected RouterInterface $router,
        protected X509CertificatServiceInterface $x509CertificatService,
        protected HttpClientInterface $client,
        protected CacheInterface $cache,
        protected LoggerInterface $logger,
    ) {
    }

    public function getOwnEntityDescriptor(string $provider): EntityDescriptor
    {
        $providerConfiguration = $this->configurationService->getByProvider($provider);

        if (Mode::SP_INITIATED === $providerConfiguration['type']) {
            $configuration = $providerConfiguration['sp'];
        } else {
            $configuration = $providerConfiguration['idp'];
        }

        return $this->buildOwnEntityDescriptor($provider, $configuration, $providerConfiguration['type']);
    }

    public function getEntityDescriptor(string $provider): EntityDescriptor
    {
        $providerConfiguration = $this->configurationService->getByProvider($provider);

        if (Mode::SP_INITIATED === $providerConfiguration['type']) {
            $configuration = $providerConfiguration['idp'];
        } else {
            $configuration = $providerConfiguration['sp'];
        }

        $xml = $this->getMetadataXml($configuration);

        if (empty($xml)) {
            throw new RuntimeException('No metadata found');
        }

        return $this->getEntityDescriptorFromXml($xml, $configuration['entity_id'] ?? null);
    }

    public function clearCache(string $provider): void
    {
        $this->logger->debug('Clearing cache for provider {provider}', ['provider' => $provider]);

        $providerConfiguration = $this->configurationService->getByProvider($provider);

        if (Mode::SP_INITIATED === $providerConfiguration['type']) {
            $configuration = $providerConfiguration['idp'];
        } else {
            $configuration = $providerConfiguration['sp'];
        }

        $tokenId = $this->getTokenId($configuration);

        $this->cache->delete($tokenId);
    }

    public function getMetadataXml(array $config): string
    {
        $metadata = $config['metadata'] ?? '';

        if (!filter_var($metadata, FILTER_VALIDATE_URL)) {
            if (file_exists($metadata) && is_readable($metadata)) {
                $this->logger->debug('Getting metadata from file');

                $metadata = file_get_contents($metadata);

                if (false === $metadata) {
                    throw new RuntimeException('Impossible to read metadata file');
                }
            }

            $this->logger->debug('Getting metadata from string');

            return $metadata;
        }

        $tokenId = $this->getTokenId($config);
        $metadataTtl = (int) floor($config['metadata_cache_duration'] ?? self::DEFAULT_METADATA_CACHE_DURATION);

        return $this->cache->get(
            $tokenId,
            function (CacheItemInterface $item) use ($metadata, $metadataTtl, $config): string {
                $item->expiresAfter($metadataTtl);

                $this->logger->debug('Getting metadata from url and save it to cache', [
                    'metadata'    => $metadata,
                    'metadataTtl' => $metadataTtl,
                ]);

                $options = [];

                if (!empty($config['disable_ssl_verification'])) {
                    $options = [
                        'verify_peer' => false,
                        'verify_host' => false,
                    ];
                }

                $xml = $this->client->request('GET', $metadata, $options)->getContent();
                $item->set($xml);

                return $xml;
            },
        );
    }

    /**
     * @param string      $xml
     * @param string|null $entityId
     *
     * @return EntityDescriptor
     *
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

        $entityMetadata = null;
        if ($metadata instanceof EntitiesDescriptor) {
            $entityMetadata = $metadata->getByEntityId($entityId);
        }

        if (null === $entityMetadata) {
            throw new RuntimeException('No entity descriptor found');
        }

        return $entityMetadata;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return string
     */
    protected function getTokenId(array $config = []): string
    {
        $str = $config['metadata'] ?? '';

        if (empty($str)) {
            throw new RuntimeException('Impossible to generate token ID, for metadata');
        }

        return sha1((string) $str);
    }

    /**
     * @param string               $provider
     * @param array<string, mixed> $config
     * @param Mode                 $mode
     *
     * @return EntityDescriptor
     */
    protected function buildOwnEntityDescriptor(string $provider, array $config, Mode $mode): EntityDescriptor
    {
        if (Mode::SP_INITIATED === $mode) {
            $descriptor = new SpSsoDescriptor();
            $descriptor->setWantAssertionsSigned(true);
        } else {
            $descriptor = new IdpSsoDescriptor();
            $descriptor->setWantAuthnRequestsSigned(true);
        }

        $descriptor->addNameIDFormat($this->getNameIDFormat($config));

        foreach ([SamlConstants::BINDING_SAML2_HTTP_REDIRECT, SamlConstants::BINDING_SAML2_HTTP_POST] as $bindingType) {
            if ($descriptor instanceof SpSsoDescriptor && isset($config['acs'])) {
                $acsRoute = $this->getAssertionConsumerServiceRoute($provider, $config, $bindingType);

                if (null !== $acsRoute) {
                    $acs = new AssertionConsumerService();
                    $acs->setIsDefault($bindingType === $this->getDefaultAssertionConsumerServiceBinding($config));
                    $acs->setBinding($bindingType)->setLocation($acsRoute);

                    $descriptor->addAssertionConsumerService($acs);
                }
            }

            if ($descriptor instanceof IdpSsoDescriptor && isset($config['sso'])) {
                $ssoRoute = $this->getSingleSignOnServiceRoute($provider, $config, $bindingType);

                if (null !== $ssoRoute) {
                    $sso = new SingleSignOnService();
                    $sso->setBinding($bindingType)->setLocation($ssoRoute);

                    $descriptor->addSingleSignOnService($sso);
                }
            }

            $sloRoute = $this->getSingleLogoutServiceRoute($provider, $config, $bindingType);

            if (null !== $sloRoute) {
                $descriptor->addSingleLogoutService(new SingleLogoutService($sloRoute, $bindingType));
            }
        }

        $entityDescriptor = new EntityDescriptor();
        $entityDescriptor
            ->setID(Helper::generateID())
            ->setEntityID($this->getEntityId($provider, $config))
            ->addItem($descriptor)
        ;

        $samlAlgorithmSignature = $config['saml_algorithm_signature']->value;

        $credential = $this->x509CertificatService->getX509Credentials($config);
        if (null !== $credential) {
            $entityDescriptor->setSignature(
                $this->x509CertificatService->getSignature($credential, $samlAlgorithmSignature),
            );

            if ($descriptor instanceof SpSsoDescriptor) {
                $descriptor->setAuthnRequestsSigned(true);
            }

            $descriptor
                ->addKeyDescriptor(
                    new KeyDescriptor(KeyDescriptor::USE_SIGNING, $credential->getCertificate()),
                )
                ->addKeyDescriptor(
                    new KeyDescriptor(KeyDescriptor::USE_ENCRYPTION, $credential->getCertificate()),
                )
            ;
        }

        // @todo Ajout section organization

        // @todo Ajout section contact technical

        return $entityDescriptor;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return string
     */
    protected function getNameIDFormat(array $config): string
    {
        $nameIdFormat = $config['name_id_format'] ?? SamlConstants::NAME_ID_FORMAT_PERSISTENT;

        if (!SamlConstants::isNameIdFormatValid($nameIdFormat)) {
            throw new \InvalidArgumentException(\sprintf('Invalid NameID format "%s"', $nameIdFormat));
        }

        return $nameIdFormat;
    }

    /**
     * @param string               $provider
     * @param array<string, mixed> $config
     * @param string               $bindindType
     *
     * @return string|null
     */
    protected function getSingleSignOnServiceRoute(string $provider, array $config, string $bindindType): ?string
    {
        $route = $config['sso']['route'] ?? null;

        if (null !== $route) {
            if (!$this->hasRouteBindingType($route, $bindindType)) {
                return null;
            }

            return $this->urlGenerator
                ->generate($route, ['provider' => $provider], UrlGeneratorInterface::ABSOLUTE_URL)
            ;
        }

        $url = $config['sso']['url'] ?? null;

        if (null === $url) {
            throw new \InvalidArgumentException('SingleSignOn Service route or URL must be configured');
        }

        return $url;
    }

    /**
     * @param string               $provider
     * @param array<string, mixed> $config
     * @param string               $bindindType
     *
     * @return string|null
     */
    protected function getAssertionConsumerServiceRoute(string $provider, array $config, string $bindindType): ?string
    {
        $route = $config['acs']['route'] ?? null;

        if (null !== $route) {
            if (!$this->hasRouteBindingType($route, $bindindType)) {
                return null;
            }

            return $this->urlGenerator
                ->generate($route, ['provider' => $provider], UrlGeneratorInterface::ABSOLUTE_URL)
            ;
        }

        $url = $config['acs']['url'] ?? null;

        if (null === $url) {
            throw new \InvalidArgumentException('Assertion Consumer Service route or URL must be configured');
        }

        return $url;
    }

    /**
     * @param string               $provider
     * @param array<string, mixed> $config
     * @param string               $bindindType
     *
     * @return string|null
     */
    protected function getSingleLogoutServiceRoute(string $provider, array $config, string $bindindType): ?string
    {
        $route = $config['slo']['route'] ?? null;

        if (null !== $route) {
            if (!$this->hasRouteBindingType($route, $bindindType)) {
                return null;
            }

            return $this->urlGenerator
                ->generate($route, ['provider' => $provider], UrlGeneratorInterface::ABSOLUTE_URL)
            ;
        }

        $url = $config['slo']['url'] ?? null;

        if (null === $url) {
            throw new \InvalidArgumentException('Assertion Consumer Service route or URL must be configured');
        }

        return $url;
    }

    protected function hasRouteBindingType(string $route, string $bindingType): bool
    {
        $methods = [
            SamlConstants::BINDING_SAML2_HTTP_REDIRECT => 'GET',
            SamlConstants::BINDING_SAML2_HTTP_POST     => 'POST',
        ];

        if (!\array_key_exists($bindingType, $methods)) {
            return false;
        }

        try {
            $sfRoute = $this->router->getRouteCollection()->get($route);

            if (null === $sfRoute) {
                return false;
            }

            $routeMethods = $sfRoute->getMethods();

            if (empty($routeMethods)) {
                return true;
            }

            return \in_array($methods[$bindingType], $routeMethods, true);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return string
     */
    protected function getDefaultAssertionConsumerServiceBinding(array $config): string
    {
        $acsBindingType = $config['acs']['binding'] ?? SamlConstants::BINDING_SAML2_HTTP_POST;

        if (!SamlConstants::isBindingValid($acsBindingType)) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid Assertion Consumer Service binding "%s"', $acsBindingType),
            );
        }

        return $acsBindingType;
    }

    /**
     * @param string               $provider
     * @param array<string, mixed> $config
     *
     * @return string
     */
    protected function getEntityId(string $provider, array $config): string
    {
        $context = $this->router->getContext();
        $host = $context->getScheme() . '://' . $context->getHost();

        if (empty($config['entity_id'])) {
            return $this->urlGenerator->generate(
                'umanit_saml_metadata',
                ['provider' => $provider],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        }

        return str_replace('{{host}}', $host, $config['entity_id']);
    }
}
