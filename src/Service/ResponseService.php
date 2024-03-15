<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use DateTimeImmutable;
use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\Context\CredentialContextSet;
use LightSaml\Credential\Context\MetadataCredentialContext;
use LightSaml\Credential\X509Credential;
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Error\LightSamlSecurityException;
use LightSaml\Error\LightSamlValidationException;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\EncryptedAssertionReader;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\XmlDSig\AbstractSignatureReader;
use LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria;
use LightSaml\Resolver\Endpoint\Criteria\LocationCriteria;
use LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria;
use LightSaml\Resolver\Endpoint\DescriptorTypeEndpointResolver;
use LightSaml\Validator\Model\Assertion\AssertionTimeValidator;
use LightSaml\Validator\Model\Assertion\AssertionValidatorInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Umanit\SamlBundle\Validator\TimeValidatorInterface;

class ResponseService implements ResponseServiceInterface
{
    private const MAX_VALIDATION_TIME_FOR_ID = 120;
    private const ALLOWED_SECONDS_SKEW = 120;

    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected X509CertificatServiceInterface $x509CertificatService,
        protected IdpMetadataServiceInterface $idpMetadataService,
        protected SpMetadataServiceInterface $spMetadataService,
        protected AdapterInterface $cache,
        protected AssertionValidatorInterface $assertionValidator,
        protected TimeValidatorInterface $timeValidator
    ) {
    }

    public function getSamlMessage(HttpFoundationRequest $request): ?Response
    {
        $messageContext = new MessageContext();
        $bindingFactory = new BindingFactory();
        $bindingType = $bindingFactory->detectBindingType($request);
        $bindingFactory->create($bindingType)->receive($request, $messageContext);
        $messageContext->setBindingType($bindingType);

        $response = $messageContext->asResponse();

        if (!$response instanceof Response) {
            return null;
        }

        return $response;
    }

    public function validateSamlMessage(string $provider, Response $message): void
    {
        $assertion = $message->getFirstAssertion();

        if (null === $assertion) {
            throw new LightSamlValidationException('No assertion found in response');
        }

        // On vérifie que le status de la réponse est bien un succès
        $this->validateStatus($message);

        // On récupère les assertions chiffrées ont les décrypte et on les met dans le message
        $this->decryptAssertions($provider, $message);

        // On vérifie que les assertions sont bien valides
        $this->assertionValidator->validateAssertion($assertion);

        // On vérifie que l'Issuer de la réponse est bien celui attendu
        $this->validateIssuer($provider, $message);

        // On vérifie que le recipient de la réponse est bien celui attendu
        $this->validateRecipient($provider, $message);

        // On vérifie qu'il n'y a pas plusieurs vérifications de réponse avec le même ID dans un laps de temps
        $this->validateRepeatedId($message);

        // On vérifie que le timestamp de la réponse est bien valide
        $this->timeValidator->validateAssertion($assertion);

        // On vérifie que la signature de la réponse est bien valide
        $this->validateSignature($provider, $message);

        $nameIdValue = $assertion->getSubject()?->getNameID()?->getValue();

        if (null === $nameIdValue) {
            throw new LightSamlValidationException('No NameID value found in response');
        }
    }

    private function validateStatus(Response $message): void
    {
        $status = $message->getStatus();

        if (null === $status) {
            throw new LightSamlValidationException('No status found in response');
        }

        if (!$status->isSuccess()) {
            throw new LightSamlValidationException('Response is not successful');
        }
    }

    private function decryptAssertions(string $provider, Response $message): void
    {
        $credentials = $this->x509CertificatService->getSpCredential($provider);

        if (null === $credentials) {
            return;
        }

        $reader = $message->getFirstEncryptedAssertion();

        if (!$reader instanceof EncryptedAssertionReader) {
            return;
        }

        $assertion = $reader->decryptAssertion($credentials->getPrivateKey(), new DeserializationContext());
        $message->addAssertion($assertion);
    }

    private function validateIssuer(string $provider, Response $message): void
    {
        $issuer = $message->getIssuer();

        if (null === $issuer) {
            throw new LightSamlValidationException('No issuer found in response');
        }

        $idpEntityDescriptor = $this->idpMetadataService->getEntityDescriptor($provider);

        if ($idpEntityDescriptor->getEntityID() !== $issuer->getValue()) {
            throw new LightSamlValidationException('Issuer does not match IdP entity descriptor');
        }
    }

    private function validateRecipient(string $provider, Response $message): void
    {
        $assertion = $message->getFirstAssertion();

        if (null === $assertion) {
            throw new LightSamlValidationException('No assertion found in response');
        }

        $subject = $assertion->getSubject();

        if (null === $subject) {
            throw new LightSamlValidationException('No subject found in response');
        }

        $subjectConfirmation = $subject->getFirstSubjectConfirmation();

        if (null === $subjectConfirmation) {
            throw new LightSamlValidationException('No subject confirmation found in response');
        }

        $subjectConfirmationData = $subjectConfirmation->getSubjectConfirmationData();

        if (null === $subjectConfirmationData) {
            throw new LightSamlValidationException('No subject confirmation data found in response');
        }

        $recipient = $subjectConfirmationData->getRecipient();

        if (null === $recipient) {
            throw new LightSamlValidationException('No recipient found in response');
        }

        $criteriaSet = new CriteriaSet([
            new DescriptorTypeCriteria(SpSsoDescriptor::class),
            new ServiceTypeCriteria(AssertionConsumerService::class),
            new LocationCriteria($recipient),
        ]);

        $spEntityDescriptor = $this->spMetadataService->getEntityDescriptor($provider);
        $endpoints = (new DescriptorTypeEndpointResolver())
            ->resolve($criteriaSet, $spEntityDescriptor->getAllEndpoints())
        ;

        if (empty($endpoints)) {
            throw new LightSamlValidationException(
                sprintf('No endpoint found for recipient "%s"', $recipient)
            );
        }
    }

    private function validateRepeatedId(Response $message): void
    {
        $assertion = $message->getFirstAssertion();

        if (null === $assertion) {
            throw new LightSamlValidationException('No assertion found in response');
        }

        $id = $assertion->getID();
        $issuerValue = $message->getIssuer()?->getValue();

        $key = sprintf('%s-%s', $issuerValue, $id);

        if ($this->cache->hasItem($key)) {
            throw new LightSamlValidationException('Repeated ID found in response');
        }

        $time = self::MAX_VALIDATION_TIME_FOR_ID;

        $item = $this->cache->getItem($key);
        $item->expiresAfter($time);
        $this->cache->save($item);
    }

    private function validateSignature(string $provider, Response $message): void
    {
        $idpEntityDescriptor = $this->idpMetadataService->getEntityDescriptor($provider);
        $idpSsoDescriptor = $idpEntityDescriptor->getFirstIdpSsoDescriptor();

        if (null === $idpSsoDescriptor) {
            throw new LightSamlValidationException('No IdP SSO descriptor found in IdP entity descriptor');
        }

        $signatureReader = $message->getSignature() ?: $message->getFirstAssertion()?->getSignature();

        if (!$signatureReader instanceof AbstractSignatureReader) {
            throw new LightSamlValidationException('No signature found in response');
        }

        /** @var KeyDescriptor[] $keyDescriptors */
        $keyDescriptors = $idpSsoDescriptor->getAllKeyDescriptors();

        $credentialCandidates = [];

        foreach ($keyDescriptors as $keyDescriptor) {
            $credentialCandidates[] = (new X509Credential($keyDescriptor->getCertificate()))
                ->setEntityId($idpEntityDescriptor->getEntityID())
                ->addKeyName($keyDescriptor->getCertificate()?->getName())
                ->setUsageType($keyDescriptor->getUse())
                ->setCredentialContext(
                    new CredentialContextSet([
                        new MetadataCredentialContext($keyDescriptor, $idpSsoDescriptor, $idpEntityDescriptor),
                    ])
                )
            ;
        }

        // On vérifie que la signature est bien faite avec une des clés publiques de l'IdP
        if (null !== ($x509Thumbprint = $signatureReader->getKey()?->getX509Thumbprint())) {
            $result = [];

            foreach ($credentialCandidates as $credentialCandidate) {
                if ($credentialCandidate->getPublicKey()?->getX509Thumbprint() === $x509Thumbprint) {
                    $result[] = $credentialCandidate;
                }
            }

            $credentialCandidates = $result;
        }

        if (empty($credentialCandidates)) {
            throw new LightSamlValidationException('No valid credential found for signature');
        }

        try {
            $credential = $signatureReader->validateMulti($credentialCandidates);
        } catch (LightSamlSecurityException $e) {
            dump(
                \openssl_error_string(),
                $credentialCandidates,
                $signatureReader->getKey()->getX509Thumbprint(),
            );

            throw $e;
        }
    }
}
