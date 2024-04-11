<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Criteria\CriteriaSet;
use LightSaml\Error\LightSamlValidationException;
use LightSaml\Model\Assertion\EncryptedAssertionReader;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\StatusResponse;
use LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria;
use LightSaml\Resolver\Endpoint\Criteria\LocationCriteria;
use LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria;
use LightSaml\Resolver\Endpoint\DescriptorTypeEndpointResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\MetadataServiceInterface;
use LightSaml\Validator\Model\Assertion\AssertionValidatorInterface;
use Umanit\SamlBundle\Service\X509CertificatServiceInterface;

class ResponseValidator implements ResponseValidatorInterface
{
    private const MAX_VALIDATION_TIME_FOR_ID = 120;

    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected MetadataServiceInterface $metadataService,
        protected X509CertificatServiceInterface $x509CertificatService,
        protected AdapterInterface $cache,
        protected AssertionValidatorInterface $assertionValidator,
        protected SignatureValidatorInterface $signatureValidator,
        protected IssuerValidatorInterface $issuerValidator,
        protected TimeValidatorInterface $timeValidator,
        protected LoggerInterface $logger
    ) {

    }

    public function validate(string $provider, Response $samlMessage, bool $strict = true): void
    {
        $assertion = $samlMessage->getFirstAssertion();

        if (null === $assertion) {
            throw new LightSamlValidationException('No assertion found in response');
        }

        $this->logger->info('Validating response status');
        $this->validateStatus($samlMessage);

        $this->logger->info('Decrypting assertions');
        $this->decryptAssertions($provider, $samlMessage);

        $this->logger->info('Validating assertion');
        $this->assertionValidator->validateAssertion($assertion);

        if ($strict) {
            $this->logger->info('Validating issuer');
            $this->issuerValidator->validate($samlMessage);

            $this->logger->info('Validating recipient');
            $this->validateRecipient($provider, $samlMessage);

            $this->logger->info('Validating repeated ID');
            $this->validateRepeatedId($samlMessage);

            $this->logger->info('Validating time');
            $this->timeValidator->validateAssertion($assertion);
        }

        $this->logger->info('Validating signature');
        $this->signatureValidator->validate($provider, $samlMessage);
    }

    protected function validateStatus(StatusResponse $samlMessage): void
    {
        $status = $samlMessage->getStatus();

        if (null === $status) {
            throw new LightSamlValidationException('No status found in response');
        }

        if (!$status->isSuccess()) {
            throw new LightSamlValidationException('Response is not successful');
        }
    }

    protected function decryptAssertions(string $provider, Response $samlMessage): void
    {
        $credentials = $this->x509CertificatService->getSpCredential($provider);

        if (null === $credentials) {
            return;
        }

        $reader = $samlMessage->getFirstEncryptedAssertion();

        if (!$reader instanceof EncryptedAssertionReader) {
            return;
        }

        $assertion = $reader->decryptAssertion($credentials->getPrivateKey(), new DeserializationContext());

        $samlMessage->addAssertion($assertion);
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

        $spEntityDescriptor = $this->metadataService->getOwnEntityDescriptor($provider);

        $endpoints = (new DescriptorTypeEndpointResolver())
            ->resolve($criteriaSet, $spEntityDescriptor->getAllEndpoints())
        ;

        if (empty($endpoints)) {
            throw new LightSamlValidationException(
                sprintf('No endpoint found for recipient "%s"', $recipient)
            );
        }
    }

    private function validateRepeatedId(Response $samlMessage): void
    {
        $assertion = $samlMessage->getFirstAssertion();

        if (null === $assertion) {
            throw new LightSamlValidationException('No assertion found in response');
        }

        $id = $assertion->getID();
        $issuerValue = $samlMessage->getIssuer()?->getValue();

        $key = sprintf('%s-%s', $issuerValue, $id);

        if ($this->cache->hasItem($key)) {
            throw new LightSamlValidationException('Repeated ID found in response');
        }

        $time = self::MAX_VALIDATION_TIME_FOR_ID;

        $item = $this->cache->getItem($key);
        $item->expiresAfter($time);
        $this->cache->save($item);
    }
}
