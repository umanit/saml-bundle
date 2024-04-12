<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\Response;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

interface ResponseServiceInterface
{
    public function getResponseSamlMessage(HttpFoundationRequest $request): ?Response;

    public function validate(string $provider, Response $samlMessage, bool $strict = true): void;
}
