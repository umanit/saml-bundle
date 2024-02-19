<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Metadata\EntityDescriptor;

interface IdpMetadataServiceInterface
{
    public function getEntityDescriptor(string $provider): EntityDescriptor;

    public function clearCache(string $provider): void;
}
