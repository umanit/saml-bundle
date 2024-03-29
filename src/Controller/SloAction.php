<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\MetadataServiceInterface;

#[Route('slo/{provider<\w+>}', name: 'umanit_saml_slo', methods: ['GET', 'POST'])]
class SloAction extends AbstractController
{
    public function __invoke(
        string $provider,
        MetadataServiceInterface $metadata
    ) {
        dd($metadata->getEntityDescriptor($provider));

        return $this->json([]);
    }
}
