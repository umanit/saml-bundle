<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Service\SpMetadataServiceInterface;

#[Route('acs/{provider<\w+>}', name: 'umanit_saml_acs', methods: ['GET', 'POST'])]
class AcsAction extends AbstractController
{
    public function __invoke(
        string $provider,
        Request $request,
        SpMetadataServiceInterface $spMetadataService
    ) {

        // Debug : symfony console server:dump

        $messageContext = new MessageContext();
        $bindingFactory = new BindingFactory();
        $bindingType = $bindingFactory->detectBindingType($request);
        $bindingFactory->create($bindingType)->receive($request, $messageContext);
        $messageContext->setBindingType($bindingType);

        dd($messageContext->getMessage());

        return $this->json([]);
    }
}
