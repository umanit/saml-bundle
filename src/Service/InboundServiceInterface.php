<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\SamlMessage;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

interface InboundServiceInterface
{
    public function getSamlMessage(HttpFoundationRequest $request): SamlMessage;

    public function validateSignature(string $provider, SamlMessage $samlMessage): void;

    public function validateIssuer(SamlMessage $samlMessage): void;
}
