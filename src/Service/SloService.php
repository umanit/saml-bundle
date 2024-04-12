<?php
declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\LogoutResponse;
use Symfony\Component\HttpFoundation\Request;

class SloService implements SloServiceInterface
{
    public function __construct(
        protected SamlMessageServiceInterface $samlMessageService
    ) {
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
}
