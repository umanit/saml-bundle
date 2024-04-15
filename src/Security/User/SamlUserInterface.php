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
     * @return array<mixed>
     */
    public function getSamlAttributes(): array;

    /**
     * @param array<mixed> $samlAttributes
     */
    public function setSamlAttributes(array $samlAttributes): void;
}
