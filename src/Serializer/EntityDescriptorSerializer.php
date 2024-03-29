<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Serializer;

use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Metadata\EntityDescriptor;

class EntityDescriptorSerializer implements EntityDescriptorSerializerInterface
{
    public function toXml(EntityDescriptor $entityDescriptor): string
    {
        $serializationContext = new SerializationContext();
        $entityDescriptor->serialize($serializationContext->getDocument(), $serializationContext);

        $xml = $serializationContext->getDocument()->saveXML();

        if (false === $xml) {
            throw new \InvalidArgumentException('Unable to serialize EntityDescriptor');
        }

        return $xml;
    }
}
