<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

readonly class SamlProviderBadge implements BadgeInterface
{
    public function __construct(
        private string $providerKey,
    ) {
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
