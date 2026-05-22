<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Umanit\SamlBundle\Service\SloServiceInterface;

#[Route('slo/{provider<\w+>}', name: 'umanit_saml_slo', methods: ['GET', 'POST'])]
#[IsGranted('PUBLIC_ACCESS')]
class SloAction extends AbstractController
{
    public function __invoke(
        string $provider,
        SloServiceInterface $sloService,
        Request $request,
        ?LoggerInterface $umanitSamlLogger = null,
    ): Response {
        $response = null;

        try {
            if ($request->query->has('SAMLResponse') || $request->request->has('SAMLResponse')) {
                $response = $sloService->logout($request, $provider);
            }

            if ($request->query->has('SAMLRequest') || $request->request->has('SAMLRequest')) {
                $response = $sloService->sendLogoutResponse($provider, $request);
            }
        } catch (\Throwable $e) {
            $umanitSamlLogger?->error('SAML SloAction', [
                'exception'      => $e,
                'provider'       => $provider,
                'method'         => $request->getMethod(),
                'uri'            => $request->getRequestUri(),
                'query_params'   => $request->query->all(),
                'request_params' => $request->request->all(),
            ]);
        }

        return $response ?? $this->redirect('/');
    }
}
