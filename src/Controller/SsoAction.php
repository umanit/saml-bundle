<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use LightSaml\Model\Protocol\AuthnRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\ResponseServiceInterface;

#[Route('sso/{provider<\w+>}', name: 'umanit_saml_sso', methods: ['GET', 'POST'])]
class SsoAction extends AbstractController
{
    public function __invoke(
        Request $request,
        ResponseServiceInterface $responseService,
        string $provider
    ): void {
        /** @var AuthnRequest $samlMessage */
        $samlMessage = $responseService->getAuthNRequestResponse($request);

        dd($samlMessage);
        $responseService->validateSamlMessage($provider, $samlMessage);

        throw new \LogicException('Method not implemented');
    }
}
