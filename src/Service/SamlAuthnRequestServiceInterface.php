<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\AuthnRequest;

interface SamlAuthnRequestServiceInterface
{
    public function generate(string $provider): AuthnRequest;
}
