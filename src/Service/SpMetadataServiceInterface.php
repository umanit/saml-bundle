<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Metadata\EntityDescriptor;

interface SpMetadataServiceInterface
{
    public function getEntityDescriptor(string $provider): EntityDescriptor;

    public function toXML(EntityDescriptor $entityDescriptor): string;
}
