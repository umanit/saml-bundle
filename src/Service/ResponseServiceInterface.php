<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

interface ResponseServiceInterface
{
    public function getSamlMessage(HttpFoundationRequest $request): ?Response;

    public function validateSamlMessage(string $provider, Response $message): void;
}
