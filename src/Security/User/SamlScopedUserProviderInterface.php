<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

interface SamlScopedUserProviderInterface extends UserProviderInterface
{
    public function loadUserByIdentifierAndProvider(string $identifier, string $provider, array $attributes = []): UserInterface;
}
