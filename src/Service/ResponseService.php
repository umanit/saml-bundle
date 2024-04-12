<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Protocol\Response;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Umanit\SamlBundle\Validator\ResponseValidatorInterface;

class ResponseService implements ResponseServiceInterface
{
    public function __construct(
        protected readonly ResponseValidatorInterface $responseValidator
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

    public function validate(string $provider, Response $samlMessage, bool $strict = true): void
    {
        $this->responseValidator->validate($provider, $samlMessage, $strict);
    }
}
