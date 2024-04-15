<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Context\Profile\MessageContext;
use Symfony\Component\HttpFoundation\Request;

interface SamlMessageServiceInterface
{
    public function getSamlMessage(Request $request): MessageContext;
}
