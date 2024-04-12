<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Serializer;

use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\SamlElementInterface;

class SamlElementSerializer implements SamlElementSerializerInterface
{
    public function toXml(SamlElementInterface $samlElement): string
    {
        $serializationContext = new SerializationContext();
        $samlElement->serialize($serializationContext->getDocument(), $serializationContext);

        $xml = $serializationContext->getDocument()->saveXML();

        if (false === $xml) {
            throw new \InvalidArgumentException('Unable to serialize saml element');
        }

        return $xml;
    }
}
