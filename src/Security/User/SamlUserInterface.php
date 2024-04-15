<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

interface SamlUserInterface
{
    public function setSamlIdentifier(string $identifier): self;

    public function setRoles(array $roles): self;

    public function getSamlAttributes(): array;

    public function setSamlAttributes(array $samlAttributes): void;
}
