<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

interface SamlUserInterface
{
    public function getSamlAttributes(): array;

    public function setSamlAttributes(array $samlAttributes): void;
}
