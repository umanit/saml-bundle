<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\SamlAuthnRequestServiceInterface;
use Umanit\SamlBundle\Service\SendMessageServiceInterface;

class RedirectAction extends AbstractController
{
    #[Route('redirect/{provider<\w+>}', name: 'umanit_saml_redirect')]
    public function __invoke(
        string $provider,
        Request $request,
        SamlAuthnRequestServiceInterface $authnRequestService,
        SendMessageServiceInterface $sendMessageService
    ): Response {
        $authnRequest = $authnRequestService->generate($provider);
        $xml = $authnRequestService->toXML($authnRequest);

        /*
        return $sendMessageService->send(
            $provider,
            $authnRequest,
            SamlConstants::BINDING_SAML2_HTTP_POST
        );
        */

        return $this->render('@UmanitSaml/redirect.html.twig', [
            'xml_base_64' => base64_encode($xml),
            'destination' => $authnRequest->getDestination()
        ]);
    }
}
