<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @extends UserProviderInterface<UserInterface>
 */
interface SamlUserProviderInterface extends UserProviderInterface
{
    public function loadSamlUser(string $identifier, string $provider, array $attributes = []): UserInterface;

    public function loadUserByEmail(string $email): UserInterface;

    public function refreshRole(UserInterface $user): void;
}
