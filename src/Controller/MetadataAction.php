<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Umanit\SamlBundle\Guesser\BestContentTypeGuesserInterface;
use Umanit\SamlBundle\Serializer\SamlElementSerializerInterface;
use Umanit\SamlBundle\Service\MetadataServiceInterface;

class MetadataAction implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    #[Required]
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = $this->container ?? null;
        $this->container = $container;

        return $previous;
    }

    public static function getSubscribedServices(): array
    {
        return [];
    }

    #[Route('metadata/{provider<\w+>}', name: 'umanit_saml_metadata')]
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
            $entityDescriptorSerializer->toXML($entityDescriptor),
            Response::HTTP_OK,
            ['Content-Type' => $bestContentTypeGuesser->guessForMetadataRequest($request)]
        );
    }
}
