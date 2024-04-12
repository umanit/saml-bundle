<?php
declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\LogoutResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface SloServiceInterface
{
    public function logout(Request $request, string $provider): ?Response;

    public function getLogoutResponseSamlMessage(Request $request): ?LogoutResponse;

    public function validate(string $provider, LogoutResponse $samlMessage, bool $strict = true): void;
}
