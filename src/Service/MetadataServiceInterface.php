<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

interface MetadataServiceInterface
{
    // todo provider en params
    public function generate(): string;
}
