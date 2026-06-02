<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Umanit\SamlBundle\Exception\ProviderDisabledException;
use Umanit\SamlBundle\Exception\ProviderNotFoundException;
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
        try {
            if ($request->query->has('SAMLResponse') || $request->request->has('SAMLResponse')) {
                return $sloService->logout($request, $provider);
            }

            if ($request->query->has('SAMLRequest') || $request->request->has('SAMLRequest')) {
                return $sloService->sendLogoutResponse($provider, $request);
            }
            // @formatter:off
        } catch (ProviderNotFoundException | ProviderDisabledException) {
            // @formatter:on
            return $this->redirect('/');
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

        return $this->redirect('/');
    }
}
