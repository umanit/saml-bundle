<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;

class MetadataService implements MetadataServiceInterface
{
    public function generate(): string
    {
        // On récupère la config symfony grace au Service ConfigurationService
        //
        $entityDescriptor = new EntityDescriptor();
        $entityDescriptor
            ->setID(\LightSaml\Helper::generateID())
            ->setEntityID('http://some.entity.id')
        ;

        dd($entityDescriptor);
    }
}
