<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class SamlAttributesBadge implements BadgeInterface
{
    public function __construct(
        private readonly array $attributes,
    ) {
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
