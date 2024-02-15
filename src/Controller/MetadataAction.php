<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\MetadataServiceInterface;

class MetadataAction extends AbstractController
{
    #[Route('metadata', name: 'umanit_saml_metadata')]
    public function __invoke(MetadataServiceInterface $metadataService): Response
    {
        // revoie un object metadata
        $metadataService->generate();

        // TOTO ici on return la Response avec l'objet
        return new Response("metadata");
    }
}
