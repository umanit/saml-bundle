<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Security\Http\Authenticator\Token\SamlToken;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\SloServiceInterface;

final readonly class LogoutEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConfigurationServiceInterface $configurationService,
        private SloServiceInterface $sloService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();

        if (!$token instanceof SamlToken) {
            return;
        }

        $provider = $token->getProviderKey();
        $user = $token->getUser();
        $configuration = $this->configurationService->getByProvider($provider);

        if (true !== ($configuration['enable_slo'] ?? false)) {
            return;
        }

        if (Mode::SP_INITIATED === $configuration['type']) {
            // SP initiated logout
            $response = $this->sloService->sendLogoutRequest($provider, $user);

            $event->setResponse($response);
            $event->stopPropagation();

            return;
        }

        // IDP initiated logout
        $response = $this->sloService->sendLogoutResponse($provider, $event->getRequest());

        $event->setResponse($response);
    }
}
