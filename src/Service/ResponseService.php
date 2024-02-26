<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Assertion\EncryptedAssertionReader;
use LightSaml\Model\Assertion\EncryptedElementReader;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Validator\Model\Assertion\AssertionValidator;
use LightSaml\Validator\Model\NameId\NameIdValidator;
use LightSaml\Validator\Model\Statement\StatementValidator;
use LightSaml\Validator\Model\Subject\SubjectValidator;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class ResponseService implements ResponseServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected X509CertificatServiceInterface $x509CertificatService
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



    public function validateSamlMessage(string $provider, Response $message): bool
    {
        // $config = $this->configurationService->getByProvider($provider);

        $this->decryptAssertions($provider, $message);
        $this->validateAssertions($message);

        dd($message);





        // $this->decryptAssertions();
        //
        // $this->validateAssertion();
        // $this->validateIssuer();
        // $this->validateRecipient();
        // $this->validateRepeatedId();
        // $this->validateTimestamps();
        // $this->validateSignature();

        return $messageContext;
    }


    private function decryptAssertions(string $provider, Response $message): void
    {
        $credentials = $this->x509CertificatService->getSpCredential($provider);

        if (null === $credentials) {
            return;
        }

        /** @var EncryptedAssertionReader $reader */
        $reader = $message->getFirstEncryptedAssertion();

        if (null === $reader) {
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
}
