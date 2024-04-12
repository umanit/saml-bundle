<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class SamlAttributesBadge implements BadgeInterface
{
    public function __construct(
        private readonly array $attributes,
        private ?string $groupName = null,
        private ?string $groupRequired = null
    ) {
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function getGroupRequired(): ?string
    {
        return $this->groupRequired;
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
