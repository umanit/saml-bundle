<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Protocol\SamlMessage;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Umanit\SamlBundle\Validator\IssuerValidatorInterface;
use Umanit\SamlBundle\Validator\SignatureValidatorInterface;

class InboundService implements InboundServiceInterface
{
    public function __construct(
        protected readonly ConfigurationServiceInterface $configurationService,
        protected readonly SignatureValidatorInterface $signatureValidator,
        protected readonly IssuerValidatorInterface $issuerValidator
    ) {
    }

    public function getSamlMessage(HttpFoundationRequest $request): SamlMessage
    {
        $messageContext = new MessageContext();
        $bindingFactory = new BindingFactory();
        $bindingType = $bindingFactory->detectBindingType($request);
        $bindingFactory->create($bindingType)->receive($request, $messageContext);
        $messageContext->setBindingType($bindingType);

        return $messageContext->getMessage();
    }

    public function validateSignature(string $provider, SamlMessage $samlMessage): void
    {
        $this->signatureValidator->validate($provider, $samlMessage);
    }

    public function validateIssuer(SamlMessage $samlMessage): void
    {
        $this->issuerValidator->validate($samlMessage);
    }
}
