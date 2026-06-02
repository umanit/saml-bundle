<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Exception\ProviderDisabledException;
use Umanit\SamlBundle\Exception\ProviderNotFoundException;
use Umanit\SamlBundle\Security\Http\Authenticator\Token\SamlToken;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\SloServiceInterface;

final readonly class LogoutEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConfigurationServiceInterface $configurationService,
        private SloServiceInterface $sloService,
        private LoggerInterface $logger,
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

        try {
            $configuration = $this->configurationService->getByProvider($provider);
            // @formatter:off
        } catch (ProviderNotFoundException | ProviderDisabledException) {
            // @formatter:on
            return;
        } catch (\Throwable $e) {
            $this->logger->error('SAML Authentication getting logout failed', ['exception' => $e]);

            return;
        }

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
