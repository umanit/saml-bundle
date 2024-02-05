<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\SamlAuthnRequestServiceInterface;

class RedirectionAction extends AbstractController
{
    #[Route('redirection/{provider}', name: 'umanit_saml_redirection')]
    public function __invoke(
        Request $request,
        string $provider,
        SamlAuthnRequestServiceInterface $authnRequestService
    ): Response {
        $authnRequestDto = $authnRequestService->generate($provider);

        return $this->render('redirection.html.twig', [
            'authn_request_dto' => $authnRequestDto,
        ]);
    }
}
