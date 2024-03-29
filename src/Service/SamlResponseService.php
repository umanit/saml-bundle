<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use DateTime;
use LightSaml\ClaimTypes;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\Attribute;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\AudienceRestriction;
use LightSaml\Model\Assertion\AuthnContext;
use LightSaml\Model\Assertion\AuthnStatement;
use LightSaml\Model\Assertion\Conditions;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Assertion\SubjectConfirmationData;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\SamlConstants;

readonly class SamlResponseService implements SamlResponseServiceInterface
{
    public function __construct(
        private ConfigurationServiceInterface $configurationService,
        private MetadataServiceInterface $metadataService,
        private X509CertificatServiceInterface $x509CertificatService
    ) {
    }

    public function getSamlResponse(string $provider, string $nameIdValue): Response
    {
        // IDP
        $ownEntityDescriptor = $this->metadataService->getOwnEntityDescriptor($provider);

        // SP
        $entityDescriptor = $this->metadataService->getEntityDescriptor($provider);

        $acs = $entityDescriptor->getFirstSpSsoDescriptor()?->getFirstAssertionConsumerService();
        $issuer = $ownEntityDescriptor->getEntityID();

        // @TODO : determine the NameID format
        $nameId = new NameID($nameIdValue, SamlConstants::NAME_ID_FORMAT_EMAIL);

        $response = new Response();
        $response
            ->addAssertion($assertion = new Assertion())
            ->setStatus(
                new Status(
                    new StatusCode(
                        SamlConstants::STATUS_SUCCESS
                    )
                )
            )
            ->setID(Helper::generateID())
            ->setIssueInstant(new DateTime())
            ->setDestination($acs?->getLocation())
            ->setIssuer(new Issuer($issuer))
        ;

        $assertion
            ->setId(Helper::generateID())
            ->setIssueInstant(new DateTime())
            ->setIssuer(new Issuer($issuer))
            ->setSubject(
                (new Subject())
                    ->setNameID(
                        $nameId
                    )
                    ->addSubjectConfirmation(
                        (new SubjectConfirmation())
                            ->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER)
                            ->setSubjectConfirmationData(
                                (new SubjectConfirmationData())
                                    ->setInResponseTo('id_of_the_authn_request')
                                    ->setNotOnOrAfter(new DateTime('+1 MINUTE'))
                                    ->setRecipient($acs?->getLocation())
                            )
                    )
            )
            ->setConditions(
                (new Conditions())
                    ->setNotBefore(new DateTime())
                    ->setNotOnOrAfter(new DateTime('+1 MINUTE'))
                    ->addItem(
                        new AudienceRestriction([$acs?->getLocation()])
                    )
            )
            ->addItem(
                (new AttributeStatement())
                    ->addAttribute(
                        new Attribute(
                            ClaimTypes::EMAIL_ADDRESS,
                            'email@domain.com'
                        )
                    )
                    ->addAttribute(
                        new Attribute(
                            ClaimTypes::COMMON_NAME,
                            'x123'
                        )
                    )
            )
            ->addItem(
                (new AuthnStatement())
                    ->setAuthnInstant(new DateTime('-10 MINUTE'))
                    ->setSessionIndex('_some_session_index')
                    ->setAuthnContext(
                        (new AuthnContext())
                            ->setAuthnContextClassRef(
                                SamlConstants::AUTHN_CONTEXT_PASSWORD_PROTECTED_TRANSPORT
                            )
                    )
            )
        ;

        $credential = $this->x509CertificatService->getIdpCredential($provider);

        if (null === $credential) {
            throw new \RuntimeException('No Credential found.');
        }

        $config = $this->configurationService->getByProvider($provider);
        $samlAlgorithmSignature = $config['idp']['saml_algorithm_signature']->value;
        $response->setSignature($this->x509CertificatService->getSignature($credential, $samlAlgorithmSignature));

        return $response;
    }

    public function toXML(Response $response): string
    {
        $serializationContext = new SerializationContext();
        $response->serialize($serializationContext->getDocument(), $serializationContext);

        $xml = $serializationContext->getDocument()->saveXML();

        if (false === $xml) {
            throw new \RuntimeException('Unable to save XML');
        }

        return $xml;
    }
}
