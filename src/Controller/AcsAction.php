<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\SpMetadataServiceInterface;

#[Route('acs/{provider<\w+>}', name: 'umanit_saml_acs', methods: ['GET', 'POST'])]
class AcsAction extends AbstractController
{
    public function __invoke(
        string $provider,
        SpMetadataServiceInterface $spMetadataService
    ) {

        dd($spMetadataService->getEntityDescriptor($provider));

        return $this->json([]);
    }
}
