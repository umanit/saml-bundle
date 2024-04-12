<?php
declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Protocol\Response;
use Symfony\Component\HttpFoundation\Request;

interface SamlMessageServiceInterface
{
    public function getSamlMessage(Request $request): MessageContext;

    public function validate(string $provider, Response $samlMessage, bool $strict = true): void;
}
