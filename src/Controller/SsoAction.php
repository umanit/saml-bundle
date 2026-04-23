<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Umanit\SamlBundle\Service\InboundServiceInterface;

#[Route('sso/{provider<\w+>}', name: 'umanit_saml_sso', methods: ['GET', 'POST'])]
#[IsGranted('PUBLIC_ACCESS')]
class SsoAction extends AbstractController
{
    public function __invoke(
        Request $request,
        InboundServiceInterface $inboundService,
        string $provider
    ): void {
        $samlMessage = $inboundService->getSamlMessage($request);
        $inboundService->validateSignature($provider, $samlMessage);
        $inboundService->validateIssuer($samlMessage);

        throw new \LogicException('Method not implemented');
    }
}
