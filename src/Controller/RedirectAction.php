<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Event\BeforeSamlResponseEvent;
use Umanit\SamlBundle\Serializer\SamlElementSerializerInterface;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\SamlAuthnRequestServiceInterface;
use Umanit\SamlBundle\Service\SamlResponseServiceInterface;

class RedirectAction extends AbstractController
{
    #[Route('redirect/{provider<\w+>}', name: 'umanit_saml_redirect')]
    public function __invoke(
        string $provider,
        Request $request,
        EventDispatcherInterface $dispatcher,
        SamlAuthnRequestServiceInterface $authnRequestService,
        ConfigurationServiceInterface $configurationService,
        SamlResponseServiceInterface $samlResponseService,
        SamlElementSerializerInterface $samlElementSerializer,
    ): Response {
        try {
            $config = $configurationService->getByProvider($provider);

            if ($config['type'] === Mode::IDP_INITIATED) {
                $event = new BeforeSamlResponseEvent($provider);
                $dispatcher->dispatch($event);

                $samlMessage = $samlResponseService->getSamlResponse(
                    $provider,
                    $event->nameIdFormat,
                    $event->attributes
                );
                $xml = $samlElementSerializer->toXML($samlMessage);

                $type = 'SAMLResponse';
            } else {
                $samlMessage = $authnRequestService->generate($provider);
                $xml = $authnRequestService->toXML($samlMessage);

                $type = 'SAMLRequest';
            }
        } catch (\Throwable) {
            throw $this->createNotFoundException();
        }

        return $this->render('@UmanitSaml/redirect.html.twig', [
            'xml_base_64' => base64_encode($xml),
            'destination' => $samlMessage->getDestination(),
            'type'        => $type,
        ]);
    }
}
