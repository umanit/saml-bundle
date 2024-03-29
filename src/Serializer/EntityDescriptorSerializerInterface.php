<?php

namespace Umanit\SamlBundle\Serializer;

use LightSaml\Model\Metadata\EntityDescriptor;

interface EntityDescriptorSerializerInterface
{
    public function toXml(EntityDescriptor $entityDescriptor): string;
}
