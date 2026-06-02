<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Event\BeforeSamlResponseEvent;
use Umanit\SamlBundle\Serializer\SamlElementSerializerInterface;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\SamlAuthnRequestServiceInterface;
use Umanit\SamlBundle\Service\SamlResponseServiceInterface;

#[Route('redirect/{provider<\w+>}', name: 'umanit_saml_redirect')]
#[IsGranted('PUBLIC_ACCESS')]
class RedirectAction extends AbstractController
{
    public function __invoke(
        string $provider,
        EventDispatcherInterface $dispatcher,
        SamlAuthnRequestServiceInterface $authnRequestService,
        ConfigurationServiceInterface $configurationService,
        SamlResponseServiceInterface $samlResponseService,
        SamlElementSerializerInterface $samlElementSerializer,
        LoggerInterface $logger,
    ): Response {
        try {
            $config = $configurationService->getByProvider($provider);

            if (Mode::IDP_INITIATED === $config['type']) {
                $event = new BeforeSamlResponseEvent($provider);
                $dispatcher->dispatch($event);

                $samlMessage = $samlResponseService->getSamlResponse(
                    $provider,
                    $event->nameIdFormat,
                    $event->attributes,
                );
                $xml = $samlElementSerializer->toXml($samlMessage);

                $type = 'SAMLResponse';
            } else {
                $samlMessage = $authnRequestService->generate($provider);
                $xml = $authnRequestService->toXML($samlMessage);

                $type = 'SAMLRequest';
            }
        } catch (\Throwable $e) {
            $logger->error('SSO redirect error : ' . $e->getMessage());

            throw $this->createNotFoundException();
        }

        return $this->render($configurationService->getRedirectTemplate(), [
            'xml_base_64' => base64_encode($xml),
            'destination' => $samlMessage->getDestination(),
            'type'        => $type,
        ]);
    }
}
