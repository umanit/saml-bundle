<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\Response;

interface SamlResponseServiceInterface
{
    public function getSamlResponse(string $provider, string $nameIdValue): Response;

    public function toXML(Response $response): string;
}
