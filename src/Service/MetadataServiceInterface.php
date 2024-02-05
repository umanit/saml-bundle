<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

interface MetadataServiceInterface
{
    public function generate(): string;
}
