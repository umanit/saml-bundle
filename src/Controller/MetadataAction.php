<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Umanit\SamlBundle\Guesser\BestContentTypeGuesserInterface;
use Umanit\SamlBundle\Serializer\SamlElementSerializerInterface;
use Umanit\SamlBundle\Service\MetadataServiceInterface;

#[Route('metadata/{provider<\w+>}', name: 'umanit_saml_metadata')]
#[IsGranted('PUBLIC_ACCESS')]
class MetadataAction extends AbstractController
{
    public function __invoke(
        string $provider,
        Request $request,
        MetadataServiceInterface $metadataService,
        SamlElementSerializerInterface $entityDescriptorSerializer,
        BestContentTypeGuesserInterface $bestContentTypeGuesser
    ): Response {
        try {
            $entityDescriptor = $metadataService->getOwnEntityDescriptor($provider);
        } catch (\Throwable $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        return new Response(
            $entityDescriptorSerializer->toXml($entityDescriptor),
            Response::HTTP_OK,
            ['Content-Type' => $bestContentTypeGuesser->guessForMetadataRequest($request)]
        );
    }
}
