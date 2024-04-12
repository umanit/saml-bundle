<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Serializer;

use LightSaml\Model\SamlElementInterface;

interface SamlElementSerializerInterface
{
    public function toXml(SamlElementInterface $samlElement): string;
}
