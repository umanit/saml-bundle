<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\Response;

interface SamlResponseServiceInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function getSamlResponse(string $provider, string $nameIdValue, array $attributes = []): Response;

    public function toXML(Response $response): string;
}
