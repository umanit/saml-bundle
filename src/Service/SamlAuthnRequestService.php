<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\NameIDPolicy;
use LightSaml\SamlConstants;

class SamlAuthnRequestService implements SamlAuthnRequestServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected IdpMetadataServiceInterface $idpMetadataService,
        protected SpMetadataServiceInterface $spMetadataService
    ) {
    }

    public function generate(string $provider): AuthnRequest
    {
        $config = $this->configurationService->getByProvider($provider);
        $idpBindingType = $config['idp']['sso']['binding'] ?? SamlConstants::BINDING_SAML2_HTTP_REDIRECT;

        if (!SamlConstants::isBindingValid($idpBindingType)) {
            throw new \InvalidArgumentException(sprintf('Invalid binding type "%s"', $idpBindingType));
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
            ->setProtocolBinding($acsService->getBinding())
            ->setIssueInstant(new \DateTime())
            ->setDestination($idpSsoService->getLocation())
            ->setNameIDPolicy((new NameIDPolicy())->setFormat($nameIdFormat))
            ->setIssuer(new Issuer($spEntityDescriptor->getEntityID()))
            ->setAssertionConsumerServiceURL($acsService->getLocation())
        ;

        return $authnRequest;
    }
}
