<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Guesser\BestContentTypeGuesserInterface;
use Umanit\SamlBundle\Serializer\EntityDescriptorSerializerInterface;
use Umanit\SamlBundle\Service\MetadataServiceInterface;

class MetadataAction extends AbstractController
{
    #[Route('metadata/{provider<\w+>}', name: 'umanit_saml_metadata')]
    public function __invoke(
        string $provider,
        Request $request,
        MetadataServiceInterface $metadataService,
        EntityDescriptorSerializerInterface $entityDescriptorSerializer,
        BestContentTypeGuesserInterface $bestContentTypeGuesser
    ): Response {
        try {
            $entityDescriptor = $metadataService->getOwnEntityDescriptor($provider);
        } catch (\Throwable $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        return new Response(
            $entityDescriptorSerializer->toXML($entityDescriptor),
            Response::HTTP_OK,
            ['Content-Type' => $bestContentTypeGuesser->guessForMetadataRequest($request)]
        );
    }
}
