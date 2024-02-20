<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use InvalidArgumentException;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Helper;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

class SpMetadataService implements SpMetadataServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected UrlGeneratorInterface $urlGenerator,
        protected RouterInterface $router
    ) {
    }

    public function getEntityDescriptor(string $provider): EntityDescriptor
    {
        $config = $this->configurationService->getByProvider($provider);

        $spSsoDescriptor = new SpSsoDescriptor();
        $spSsoDescriptor
            ->setWantAssertionsSigned(true)
            ->addNameIDFormat($this->getNameIDFormat($config['sp']))
        ;

        foreach ([SamlConstants::BINDING_SAML2_HTTP_REDIRECT, SamlConstants::BINDING_SAML2_HTTP_POST] as $bindingType) {
            $acsRoute = $this->getAssertionConsumerServiceRoute($provider, $config['sp'], $bindingType);

            if (null !== $acsRoute) {
                $spSsoDescriptor->addAssertionConsumerService(
                    (new AssertionConsumerService())
                        ->setIsDefault(
                            $bindingType === $this->getDefaultAssertionConsumerServiceBinding($config['sp'])
                        )
                        ->setBinding($bindingType)
                        ->setLocation($acsRoute)
                );
            }

            $sloRoute = $this->getSingleLogoutServiceRoute($provider, $config['sp'], $bindingType);

            if (null !== $sloRoute) {
                $spSsoDescriptor->addSingleLogoutService(new SingleLogoutService($sloRoute, $bindingType));
            }
        }

        $entityDescriptor = new EntityDescriptor();
        $entityDescriptor->setID(Helper::generateID())
                         ->setEntityID($this->getEntityId($provider, $config['sp']))
                         ->addItem($spSsoDescriptor)
        ;

        if (null !== ($credential = $this->getX509Credentials($config['sp']))) {
            $entityDescriptor->setSignature($this->signature($credential));
            $spSsoDescriptor->setAuthnRequestsSigned(true)
                            ->addKeyDescriptor(
                                new KeyDescriptor(KeyDescriptor::USE_SIGNING, $credential->getCertificate())
                            )
                            ->addKeyDescriptor(
                                new KeyDescriptor(KeyDescriptor::USE_ENCRYPTION, $credential->getCertificate())
                            )
            ;
        }

        // Ajout section organization

        // Ajout section contact technical

        return $entityDescriptor;
    }

    public function entityDescriptorToXML(EntityDescriptor $entityDescriptor): string
    {
        $serializationContext = new SerializationContext();
        $entityDescriptor->serialize($serializationContext->getDocument(), $serializationContext);

        return $serializationContext->getDocument()->saveXML();
    }

    protected function signature(X509Credential $credential): SignatureWriter
    {
        return new SignatureWriter(
            $credential->getCertificate(),
            $credential->getPrivateKey(),
            XMLSecurityDSig::SHA256
        );
    }

    protected function getX509Credentials(array $spConfig): ?X509Credential
    {
        $x509Cert = $spConfig['x509cert'] ?? null;
        $privateKey = $spConfig['private_key'] ?? null;
        $privateKeyPassphrase = $spConfig['private_key_passphrase'] ?? '';

        if (null === $x509Cert || null === $privateKey) {
            return null;
        }

        $isFile = file_exists($privateKey) && is_readable($privateKey);

        // Laisser la possibilité de gérer le niveau de chiffrement
        return new X509Credential(
            $this->makeCertificate($x509Cert),
            KeyHelper::createPrivateKey(
                $privateKey,
                $privateKeyPassphrase,
                $isFile,
                XMLSecurityKey::RSA_SHA256
            )
        );
    }

    protected function makeCertificate(?string $data): X509Certificate
    {
        $cert = new X509Certificate();

        if ($data === null) {
            return $cert;
        }

        if (file_exists($data) && is_readable($data)) {
            $data = file_get_contents($data);
        }

        if (str_starts_with($data, '-----BEGIN CERTIFICATE-----')) {
            return $cert->loadPem($data);
        }

        return $cert->setData($data);
    }

    protected function getEntityId(string $provider, array $spConfig): string
    {
        return $spConfig['entity_id'] ?? $this->urlGenerator->generate(
            'umanit_saml_metadata',
            ['provider' => $provider],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    protected function getDefaultAssertionConsumerServiceBinding(array $spConfig): string
    {
        $acsBindingType = $spConfig['acs']['binding'] ?? SamlConstants::BINDING_SAML2_HTTP_POST;

        if (!SamlConstants::isBindingValid($acsBindingType)) {
            throw new InvalidArgumentException(
                sprintf('Invalid Assertion Consumer Service binding "%s"', $acsBindingType)
            );
        }

        return $acsBindingType;
    }

    protected function getAssertionConsumerServiceRoute(string $provider, array $spConfig, string $bindindType): ?string
    {
        $route = $spConfig['acs']['route'] ?? null;

        if (null !== $route) {
            if (!$this->hasRouteBindingType($route, $bindindType)) {
                return null;
            }

            return $this->urlGenerator
                ->generate($route, ['provider' => $provider], UrlGeneratorInterface::ABSOLUTE_URL)
            ;
        }

        $url = $spConfig['acs']['url'] ?? null;

        if (null === $url) {
            throw new InvalidArgumentException('Assertion Consumer Service route or URL must be configured');
        }

        return $url;
    }

    protected function getSingleLogoutServiceRoute(string $provider, array $spConfig, string $bindindType): ?string
    {
        $route = $spConfig['slo']['route'] ?? null;

        if (null !== $route) {
            if (!$this->hasRouteBindingType($route, $bindindType)) {
                return null;
            }

            return $this->urlGenerator
                ->generate($route, ['provider' => $provider], UrlGeneratorInterface::ABSOLUTE_URL)
            ;
        }

        $url = $spConfig['slo']['url'] ?? null;

        if (null === $url) {
            throw new InvalidArgumentException('Assertion Consumer Service route or URL must be configured');
        }

        return $url;
    }

    protected function getNameIDFormat(array $spConfig): string
    {
        $nameIdFormat = $spConfig['nameIdFormat'] ?? SamlConstants::NAME_ID_FORMAT_PERSISTENT;

        if (!SamlConstants::isNameIdFormatValid($nameIdFormat)) {
            throw new InvalidArgumentException(sprintf('Invalid NameID format "%s"', $nameIdFormat));
        }

        return $nameIdFormat;
    }

    protected function hasRouteBindingType(string $route, string $bindingType): bool
    {
        $methods = [
            SamlConstants::BINDING_SAML2_HTTP_REDIRECT => 'GET',
            SamlConstants::BINDING_SAML2_HTTP_POST     => 'POST',
        ];

        if (!array_key_exists($bindingType, $methods)) {
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

            return in_array($methods[$bindingType], $routeMethods);
        } catch (Throwable $e) {
            return false;
        }
    }
}
