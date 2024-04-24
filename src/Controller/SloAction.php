<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\SloServiceInterface;

#[Route('slo/{provider<\w+>}', name: 'umanit_saml_slo', methods: ['GET', 'POST'])]
class SloAction extends AbstractController
{
    public function __invoke(
        string $provider,
        SloServiceInterface $sloService,
        Request $request,
    ): Response {
        if ($request->query->has('SAMLResponse') || $request->request->has('SAMLResponse')) {
            $response = $sloService->logout($request, $provider);
        }

        if ($request->query->has('SAMLRequest') || $request->request->has('SAMLRequest')) {
            $response = $sloService->sendLogoutResponse($provider, $request);
        }

        return $response ?? $this->redirect('/');
    }
}
