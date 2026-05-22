<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\Response;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Umanit\SamlBundle\Validator\ResponseValidatorInterface;

class ResponseService implements ResponseServiceInterface
{
    public function __construct(
        protected SamlMessageServiceInterface $samlMessageService,
        protected ResponseValidatorInterface $responseValidator,
    ) {
    }

    public function getResponseSamlMessage(HttpFoundationRequest $request): ?Response
    {
        $messageContext = $this->samlMessageService->getSamlMessage($request);

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
