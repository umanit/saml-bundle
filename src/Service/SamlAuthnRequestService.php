<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\SamlConstants;
use Umanit\SamlBundle\Dto\SamlAuthnRequestDto;

class SamlAuthnRequestService implements SamlAuthnRequestServiceInterface
{
    public function __construct(
        public ConfigurationServiceInterface $configurationService,
    ) {
    }

    public function generate(string $provider): SamlAuthnRequestDto
    {
        $config = $this->configurationService->getByProvider($provider);

        /**
         * TODO Une erreur Symfony me dit Did you forget a "use" statement for another namespace
         * alors que les namespace est bien la !
         */
        $authnRequest = new AuthnRequest();
        $authnRequest
            ->setAssertionConsumerServiceURL($config['sp']['assertionConsumerService']['url'])
            ->setProtocolBinding(SamlConstants::BINDING_SAML2_HTTP_POST)
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($config['idp']['SingleSignOnService'])
            ->setIssuer(new Issuer('https://my.site'))
        ;

        return new SamlAuthnRequestDto('test', 'url');
    }
}
