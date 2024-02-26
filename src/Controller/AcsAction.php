<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\ResponseServiceInterface;

#[Route('acs/{provider<\w+>}', name: 'umanit_saml_acs', methods: ['GET', 'POST'])]
class AcsAction extends AbstractController
{
    public function __invoke(
        string $provider,
        Request $request,
        ResponseServiceInterface $responseService
    ): Response {
        $samlMessage = $responseService->getSamlMessage($request);

        if (null === $samlMessage) {
            throw $this->createAccessDeniedException('No SAML message found');
        }

        $responseService->validateSamlMessage($provider, $samlMessage);

        return $this->json([]);
    }
}
