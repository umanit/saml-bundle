<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use DateTime;
use InvalidArgumentException;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\SamlConstants;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class SamlAuthnRequestService implements SamlAuthnRequestServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected MetadataServiceInterface $metadataService,
        protected X509CertificatServiceInterface $x509CertificatService,
        protected RequestStack $requestStack,
    ) {
    }

    public function generate(string $provider): AuthnRequest
    {
        $config = $this->configurationService->getByProvider($provider);
        $acsBindingType = $config['sp']['acs']['binding'] ?? SamlConstants::BINDING_SAML2_HTTP_REDIRECT;

        if (!SamlConstants::isBindingValid($acsBindingType)) {
            throw new InvalidArgumentException(\sprintf('Invalid binding type "%s"', $acsBindingType));
        }

        $idpEntityDescriptor = $this->metadataService->getEntityDescriptor($provider);
        $idpSsoDescriptor = $idpEntityDescriptor->getFirstIdpSsoDescriptor();

        if (null === $idpSsoDescriptor) {
            throw new RuntimeException('No IdpSsoDescriptor found.');
        }

        $idpSsoService = $idpSsoDescriptor->getFirstSingleSignOnService($acsBindingType);

        if (null === $idpSsoService) {
            throw new RuntimeException('No SingleSignOnService found.');
        }

        $spEntityDescriptor = $this->metadataService->getOwnEntityDescriptor($provider);
        $spSsoDescriptor = $spEntityDescriptor->getFirstSpSsoDescriptor();

        if (null === $spSsoDescriptor) {
            throw new RuntimeException('No SpSsoDescriptor found.');
        }

        $acsService = $spSsoDescriptor->getFirstAssertionConsumerService();

        if (null === $acsService) {
            throw new RuntimeException('No AssertionConsumerService found.');
        }

        $authnRequest = new AuthnRequest();

        $authnRequest
            ->setID(Helper::generateID())
        ;

        $authnRequest
            ->setAssertionConsumerServiceURL($acsService->getLocation())
            ->setProtocolBinding($acsBindingType)
            ->setIssueInstant(new DateTime())
            ->setDestination($idpSsoService->getLocation())
            ->setIssuer(new Issuer($spEntityDescriptor->getEntityID()))
        ;

        $isStateless = true === ($config['sp']['is_stateless'] ?? false);

        if (!$isStateless) {
            $state = Helper::generateRandomBytes(40);
            $request = $this->requestStack->getMainRequest();

            if (null === $request) {
                throw new RuntimeException();
            }

            $request->getSession()->set('state', $state);
            $authnRequest->setRelayState($state);
        }

        $spCredential = $this->x509CertificatService->getSpCredential($provider);

        if (null === $spCredential) {
            throw new RuntimeException('No Credential found.');
        }

        $samlAlgorithmSignature = $config['sp']['saml_algorithm_signature']->value;
        $authnRequest->setSignature($this->x509CertificatService->getSignature($spCredential, $samlAlgorithmSignature));

        return $authnRequest;
    }

    public function toXML(AuthnRequest $authnRequest): string
    {
        $serializationContext = new SerializationContext();
        $authnRequest->serialize($serializationContext->getDocument(), $serializationContext);

        $xml = $serializationContext->getDocument()->saveXML();

        if (false === $xml) {
            throw new RuntimeException('Unable to save XML');
        }

        return $xml;
    }
}
