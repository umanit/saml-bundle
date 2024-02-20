<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use DateTime;
use InvalidArgumentException;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\NameIDPolicy;
use LightSaml\SamlConstants;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

class SamlAuthnRequestService implements SamlAuthnRequestServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected IdpMetadataServiceInterface $idpMetadataService,
        protected SpMetadataServiceInterface $spMetadataService,
        protected X509CertificatServiceInterface $x509CertificatService,
        protected RequestStack $requestStack
    ) {
    }

    public function generate(string $provider): AuthnRequest
    {
        $config = $this->configurationService->getByProvider($provider);
        $idpBindingType = $config['idp']['sso']['binding'] ?? SamlConstants::BINDING_SAML2_HTTP_REDIRECT;

        if (!SamlConstants::isBindingValid($idpBindingType)) {
            throw new InvalidArgumentException(sprintf('Invalid binding type "%s"', $idpBindingType));
        }

        $idpEntityDescriptor = $this->idpMetadataService->getEntityDescriptor($provider);
        $idpSsoDescriptor = $idpEntityDescriptor->getFirstIdpSsoDescriptor();
        $idpSsoService = $idpSsoDescriptor->getFirstSingleSignOnService();
        $spEntityDescriptor = $this->spMetadataService->getEntityDescriptor($provider);
        $spSsoDescriptor = $spEntityDescriptor->getFirstSpSsoDescriptor();

        $acsService = $spSsoDescriptor->getFirstAssertionConsumerService();
        $nameIdFormat = $spSsoDescriptor->getAllNameIDFormats()[0] ?? SamlConstants::NAME_ID_FORMAT_PERSISTENT;

        $authnRequest = new AuthnRequest();
        $authnRequest
            ->setID(Helper::generateID())
            ->setProtocolBinding($idpSsoService->getBinding())
            ->setIssueInstant(new DateTime())
            ->setDestination($idpSsoService->getLocation())
            ->setNameIDPolicy((new NameIDPolicy())->setFormat($nameIdFormat))
            ->setIssuer(new Issuer($spEntityDescriptor->getEntityID()))
            ->setAssertionConsumerServiceURL($acsService->getLocation())
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
        $authnRequest->setSignature($this->x509CertificatService->getSignature($spCredential));

        return $authnRequest;
    }

    public function toXML(AuthnRequest $authnRequest): string
    {
        $serializationContext = new SerializationContext();
        $authnRequest->serialize($serializationContext->getDocument(), $serializationContext);

        return $serializationContext->getDocument()->saveXML();
    }
}
