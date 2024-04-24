<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

interface SloServiceInterface
{
    public function logout(Request $request, string $provider): ?Response;

    public function getLogoutResponseSamlMessage(Request $request): ?LogoutResponse;

    public function getLogoutRequestSamlMessage(Request $request): ?LogoutRequest;

    public function validate(string $provider, LogoutResponse $samlMessage, bool $strict = true): void;

    public function sendLogoutRequest(string $provider, ?UserInterface $user): Response;

    public function sendLogoutResponse(string $provider, Request $request): Response;
}
