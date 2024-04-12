<?php
declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\LogoutResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Umanit\SamlBundle\Validator\SloValidatorInterface;

class SloService implements SloServiceInterface
{
    public function __construct(
        protected SamlMessageServiceInterface $samlMessageService,
        protected SloValidatorInterface $sloValidator,
        protected Security $security
    ) {
    }

    public function logout(Request $request, string $provider): ?Response
    {
        $response = $this->getLogoutResponseSamlMessage($request);

        if (null === $response) {
            throw new LogoutException("No SAML message found");
        }

        $this->validate($provider, $response);

        return $this->security->logout(false);
    }

    public function getLogoutResponseSamlMessage(Request $request): ?LogoutResponse
    {
        $messageContext = $this->samlMessageService->getSamlMessage($request);

        $response = $messageContext->asLogoutResponse();

        if (!$response instanceof LogoutResponse) {
            return null;
        }

        return $response;
    }

    public function validate(string $provider, LogoutResponse $samlMessage, bool $strict = true): void
    {
        $this->sloValidator->validate($provider, $samlMessage, $strict);
    }
}
