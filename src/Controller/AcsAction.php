<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use _PHPStan_11268e5ee\Symfony\Component\Console\Exception\LogicException;
use Psr\Log\LoggerInterface;
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
        ResponseServiceInterface $responseService,
        LoggerInterface $umanitSamlLogger
    ): Response {
        if ($this->getParameter('kernel.environment') === 'dev') {
            $response = base64_decode($request->request->get('SAMLResponse'));

            $umanitSamlLogger->debug('SAML Response', ['response' => $response]);
        }

        $samlMessage = $responseService->getSamlMessage($request);

        if (null === $samlMessage) {
            throw $this->createAccessDeniedException('No SAML message found');
        }

        $responseService->validateSamlMessage($provider, $samlMessage);

        throw new LogicException('Method not implemented');


    }
}
