<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use DateTime;
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
use RuntimeException;

class SamlResponseService implements SamlResponseServiceInterface
{
    public function __construct(
        private readonly ConfigurationServiceInterface $configurationService,
        private readonly MetadataServiceInterface $metadataService,
        private readonly X509CertificatServiceInterface $x509CertificatService
    ) {
    }

    /**
     * @param string       $provider
     * @param string       $nameIdValue
     * @param array<mixed> $attributes
     *
     * @return Response
     */
    public function getSamlResponse(string $provider, string $nameIdValue, array $attributes = []): Response
    {
        // IDP
        $ownEntityDescriptor = $this->metadataService->getOwnEntityDescriptor($provider);

        // SP
        $entityDescriptor = $this->metadataService->getEntityDescriptor($provider);

        $idpSsoDescriptor = $entityDescriptor->getFirstIdpSsoDescriptor();
        $spSsoDescriptor = $entityDescriptor->getFirstSpSsoDescriptor();
        $acs = $spSsoDescriptor?->getFirstAssertionConsumerService();
        $issuer = $ownEntityDescriptor->getEntityID();
        $format = $idpSsoDescriptor?->getAllNameIDFormats()[0] ?? SamlConstants::NAME_ID_FORMAT_PERSISTENT;

        $nameId = new NameID($nameIdValue, $format);

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

        $notOnOrAfter = new DateTime('+1 MINUTE');
        $authnInstant = new DateTime('-10 MINUTE');

        $authnRequestId = Helper::generateID();

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
                                    ->setInResponseTo($authnRequestId)
                                    ->setNotOnOrAfter($notOnOrAfter)
                                    ->setRecipient($acs?->getLocation())
                            )
                    )
            )
            ->setConditions(
                (new Conditions())
                    ->setNotBefore(new DateTime())
                    ->setNotOnOrAfter($notOnOrAfter)
                    ->addItem(
                        new AudienceRestriction([$acs?->getLocation()])
                    )
            )
            ->addItem(
                (new AuthnStatement())
                    ->setAuthnInstant($authnInstant)
                    ->setSessionIndex(Helper::generateID())
                    ->setAuthnContext(
                        (new AuthnContext())
                            ->setAuthnContextClassRef(
                                SamlConstants::AUTHN_CONTEXT_PASSWORD_PROTECTED_TRANSPORT
                            )
                    )
            )
        ;

        $this->addAttributesStatement($assertion, $attributes);
        $this->signResponse($response, $provider);

        return $response;
    }

    private function signResponse(Response $response, string $provider): void
    {
        $credential = $this->x509CertificatService->getIdpCredential($provider);

        if (null === $credential) {
            throw new RuntimeException('No Credential found.');
        }

        $config = $this->configurationService->getByProvider($provider);
        $samlAlgorithmSignature = $config['idp']['saml_algorithm_signature']->value;
        $response->setSignature($this->x509CertificatService->getSignature($credential, $samlAlgorithmSignature));
    }

    /**
     * @param Assertion $assertion
     * @param array<mixed>     $attributes
     *
     * @return void
     */
    private function addAttributesStatement(Assertion $assertion, array $attributes = []): void
    {
        if (empty($attributes)) {
            return;
        }

        $attributesStatement = new AttributeStatement();

        foreach ($attributes as $name => $value) {
            $attributesStatement->addAttribute(new Attribute($name, $value));
        }

        $assertion->addItem($attributesStatement);
    }

    public function toXML(Response $response): string
    {
        $serializationContext = new SerializationContext();
        $response->serialize($serializationContext->getDocument(), $serializationContext);

        $xml = $serializationContext->getDocument()->saveXML();

        if (false === $xml) {
            throw new RuntimeException('Unable to save XML');
        }

        return $xml;
    }
}
