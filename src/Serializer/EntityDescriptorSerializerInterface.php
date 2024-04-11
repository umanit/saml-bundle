<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Serializer;

use LightSaml\Model\Metadata\EntityDescriptor;

interface EntityDescriptorSerializerInterface
{
    public function toXml(EntityDescriptor $entityDescriptor): string;
}
