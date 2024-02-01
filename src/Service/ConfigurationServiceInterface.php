<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

interface ConfigurationServiceInterface
{
    public function getByProvider(string $provider): ?array;
}
