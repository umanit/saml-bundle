<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class SamlAttributesBadge implements BadgeInterface
{
    /**
     * @param array<mixed> $attributes
     * @param array<mixed> $samlRestrictions
     */
    public function __construct(
        private readonly array $attributes,
        private readonly array $samlRestrictions = [],
    ) {
    }

    /**
     * @return mixed[]
     */
    public function getSamlRestrictions(): array
    {
        return $this->samlRestrictions;
    }

    /**
     * @return mixed[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
