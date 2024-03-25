<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Protocol\LogoutResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\ResponseServiceInterface;
use Umanit\SamlBundle\Service\SpMetadataServiceInterface;

#[Route('slo/{provider<\w+>}', name: 'umanit_saml_slo', methods: ['GET', 'POST'])]
class SloAction extends AbstractController
{
    public function __invoke(
        string $provider,
        SpMetadataServiceInterface $spMetadataService,
        ResponseServiceInterface $responseService,
        Request $request,
    ) {
        $response = $this->getSamlMessage($request);

        dd($response);

        return $this->json([]);
    }
}
