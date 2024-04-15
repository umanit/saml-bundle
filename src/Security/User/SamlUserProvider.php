<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlUserProvider implements SamlUserProviderInterface
{
    use UserProviderTrait;

    /**
     * @param class-string<SamlUserInterface> $userClass
     * @param array<string>                   $defaultRoles
     * @param array<mixed>                    $restrictions
     * @param array<mixed>                    $rolesMapping
     */
    public function __construct(
        protected readonly string $userClass,
        protected readonly array $defaultRoles,
        protected readonly array $restrictions = [],
        protected readonly array $rolesMapping = [],
    ) {
        if (!is_a($userClass, SamlUserInterface::class, true)) {
            throw new InvalidArgumentException(
                'The $userClass argument should be a class implementing the ' . SamlUserInterface::class . ' interface.'
            );
        }
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** @var UserInterface $user */
        $user = new $this->userClass();

        if ($user instanceof SamlUserInterface) {
            $user->setRoles($this->defaultRoles);
            $user->setSamlIdentifier($identifier);
        }

        $this->refreshRole($user);

        return $user;
    }

    public function loadUserByEmail(string $email): UserInterface
    {
        /** @var UserInterface $user */
        $user = new $this->userClass();

        if ($user instanceof SamlUserInterface) {
            $user->setRoles($this->defaultRoles);
            $user->setSamlIdentifier($email);
        }

        $this->refreshRole($user);

        return $user;
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
