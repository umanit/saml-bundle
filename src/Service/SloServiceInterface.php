<?php
declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\LogoutResponse;
use Symfony\Component\HttpFoundation\Request;

interface SloServiceInterface
{
    public function getLogoutResponseSamlMessage(Request $request): ?LogoutResponse;
}
