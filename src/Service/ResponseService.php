<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
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
use LightSaml\Model\XmlDSig\SignatureXmlReader;
use LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria;
use LightSaml\Resolver\Endpoint\Criteria\LocationCriteria;
use LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria;
use LightSaml\Resolver\Endpoint\DescriptorTypeEndpointResolver;
use LightSaml\Validator\Model\Assertion\AssertionTimeValidator;
use LightSaml\Validator\Model\Assertion\AssertionValidator;
use LightSaml\Validator\Model\NameId\NameIdValidator;
use LightSaml\Validator\Model\Statement\StatementValidator;
use LightSaml\Validator\Model\Subject\SubjectValidator;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class ResponseService implements ResponseServiceInterface
{
    private const MAX_VALIDATION_TIME_FOR_ID = 120;
    private const ALLOWED_SECONDS_SKEW = 120;

    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected X509CertificatServiceInterface $x509CertificatService,
        protected IdpMetadataServiceInterface $idpMetadataService,
        protected SpMetadataServiceInterface $spMetadataService,
        protected AdapterInterface $cache
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
        // On vérifie que le status de la réponse est bien un succès
        $this->validateStatus($message);

        // On récupère les assertions chiffrées ont les décrypte et on les met dans le message
        $this->decryptAssertions($provider, $message);

        // On vérifie que les assertions sont bien valides
        $this->validateAssertions($message);

        // On vérifie que l'Issuer de la réponse est bien celui attendu
        $this->validateIssuer($provider, $message);

        // On vérifie que le recipient de la réponse est bien celui attendu
        $this->validateRecipient($provider, $message);

        // On vérifie qu'il n'y a pas plusieurs vérifications de réponse avec le même ID dans un laps de temps
        $this->validateRepeatedId($message);

        // On vérifie que le timestamp de la réponse est bien valide
        $this->validateTimestamp($message);

        // On vérifie que la signature de la réponse est bien valide
        $this->validateSignature($provider, $message);


        $assertion = $message->getFirstAssertion();

        if (null === $assertion) {
            throw new LightSamlValidationException('No assertion found in response');
        }

        $nameIdValue = $assertion->getSubject()?->getNameID()?->getValue();

        if (null === $nameIdValue) {
            throw new LightSamlValidationException('No NameID value found in response');
        }

        $attributeStatement = $assertion->getFirstAttributeStatement();

        if ($attributeStatement instanceof AttributeStatement) {
            $attributes = $attributeStatement->getAllAttributes();
            dump($attributes);
        }


        dump($nameIdValue);
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

    private function validateAssertions(Response $message): void
    {
        $assertionValidator = new AssertionValidator(
            new NameIdValidator(),
            new SubjectValidator(new NameIdValidator()),
            new StatementValidator()
        );

        $assertionValidator->validateAssertion($message->getFirstAssertion());
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
            ->resolve($criteriaSet, $spEntityDescriptor->getAllEndpoints());

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

    private function validateTimestamp(SamlMessage $message): void
    {
        $allowedSecondsSkew = self::ALLOWED_SECONDS_SKEW;

        (new AssertionTimeValidator())
            ->validateTimeRestrictions(
                $message->getFirstAssertion(),
                (new \DateTimeImmutable())->getTimestamp(),
                $allowedSecondsSkew
            );
    }

    private function validateSignature(string $provider, Response $message): void
    {
        $idpEntityDescriptor = $this->idpMetadataService->getEntityDescriptor($provider);
        $idpSsoDescriptor = $idpEntityDescriptor->getFirstIdpSsoDescriptor();

        if (null === $idpSsoDescriptor) {
            throw new LightSamlValidationException('No IdP SSO descriptor found in IdP entity descriptor');
        }

        $keyDescriptors = array_merge(
            $idpSsoDescriptor->getAllKeyDescriptorsByUse(KeyDescriptor::USE_SIGNING),
            $idpSsoDescriptor->getAllKeyDescriptorsByUse(null),
        );

        /** @var SignatureXmlReader $signatureReader */
        $signatureReader = $message->getSignature() ?: $message->getFirstAssertion()?->getSignature();

        if (!$signatureReader instanceof SignatureXmlReader) {
            throw new LightSamlValidationException('No signature found in response');
        }

        foreach ($keyDescriptors as $keyDescriptor) {
            $key = KeyHelper::createPublicKey($keyDescriptor->getCertificate());

            try {
                if ($signatureReader->validate($key)) {
                    return;
                }
            } catch (LightSamlSecurityException) {
                continue;
            }
        }

        throw new LightSamlValidationException('No valid signature found in response');
    }
}
