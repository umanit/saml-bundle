<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlUserProvider implements SamlUserProviderInterface
{
    /**
     * @param class-string<UserInterface> $userClass
     */
    public function __construct(
        protected string $userClass,
        protected array $defaultRoles,
    ) {
        if (!is_a($userClass, UserInterface::class, true)) {
            throw new \InvalidArgumentException('The $userClass argument should be a class implementing the ' . UserInterface::class . ' interface.');
        }
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return new $this->userClass($identifier, $this->defaultRoles);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof $this->userClass) {
            throw new UnsupportedUserException();
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return is_a($class, $this->userClass, true);
    }
}
