<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

interface SamlUserInterface
{
    public function setSamlIdentifier(string $identifier): self;

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): self;

    /**
     * @return array<string, mixed>
     */
    public function getSamlAttributes(): array;

    /**
     * @param array<string, mixed> $samlAttributes
     */
    public function setSamlAttributes(array $samlAttributes): void;
}
