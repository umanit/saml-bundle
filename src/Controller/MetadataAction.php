<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\SpMetadataServiceInterface;

class MetadataAction extends AbstractController
{
    #[Route('metadata/{provider<\w+>}', name: 'umanit_saml_metadata')]
    public function __invoke(
        string $provider,
        Request $request,
        SpMetadataServiceInterface $spMetadataService
    ): Response {
        try {
            $entityDescriptor = $spMetadataService->getEntityDescriptor($provider);
        } catch (\Throwable) {
            throw $this->createNotFoundException();
        }

        $supportedContextTypes = ['application/samlmetadata+xml', 'application/xml', 'text/xml'];
        $contentType = 'text/xml';

        $acceptableContentTypes = array_flip($request->getAcceptableContentTypes());

        foreach ($supportedContextTypes as $supportedContentType) {
            if (isset($acceptableContentTypes[$supportedContentType])) {
                $contentType = $supportedContentType;
                break;
            }
        }

        return new Response(
            $spMetadataService->toXML($entityDescriptor),
            Response::HTTP_OK,
            ['Content-Type' => $contentType]
        );
    }
}
