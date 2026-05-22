<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final readonly class SamlAttributesBadge implements BadgeInterface
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<int, mixed>    $samlRestrictions
     */
    public function __construct(
        private array $attributes,
        private array $samlRestrictions = [],
    ) {
    }

    /**
     * @return array<int, mixed>
     */
    public function getSamlRestrictions(): array
    {
        return $this->samlRestrictions;
    }

    /**
     * @return array<string, mixed>
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
