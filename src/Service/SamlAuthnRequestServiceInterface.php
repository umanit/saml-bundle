<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use Umanit\SamlBundle\Dto\SamlAuthnRequestDto;

interface SamlAuthnRequestServiceInterface
{
    public function generate(string $provider): SamlAuthnRequestDto;
}
