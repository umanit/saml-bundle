<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\Response;

interface SamlResponseServiceInterface
{
    /**
     * @param string       $provider
     * @param string       $nameIdValue
     * @param array<mixed> $attributes
     *
     * @return Response
     */
    public function getSamlResponse(string $provider, string $nameIdValue, array $attributes = []): Response;

    public function toXML(Response $response): string;
}
