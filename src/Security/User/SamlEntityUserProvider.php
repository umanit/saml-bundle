<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Persistence\Proxy;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SamlEntityUserProvider implements SamlEntityUserProviderInterface
{
    use UserProviderTrait;

    protected string $class;

    /**
     * @param class-string<UserInterface> $classOrAlias
     * @param list<string>                $defaultRoles
     * @param array<string, mixed>        $restrictions
     * @param array<string, mixed>        $rolesMapping
     */
    public function __construct(
        protected readonly ManagerRegistry $registry,
        protected readonly string $classOrAlias,
        protected readonly ?string $property = null,
        protected readonly ?string $managerName = null,
        protected readonly array $defaultRoles = [],
        protected readonly array $restrictions = [],
        protected readonly array $rolesMapping = [],
        protected readonly bool $caseInsensitive = false,
    ) {
    }

    public function loadSamlUser(string $identifier, string $provider, array $attributes = []): UserInterface
    {
        $user = $this->loadUserByProperty($this->property, $identifier);
        $this->refreshRole($user);

        if ($user instanceof SamlUserInterface) {
            $user->setSamlAttributes($attributes);
        }

        return $user;
    }

    public function loadUserByEmail(string $email): UserInterface
    {
        return $this->loadUserByProperty('email', $email);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUserByProperty($this->property, $identifier);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $class = $this->getClass();

        if (!$user instanceof $class) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        $repository = $this->getRepository();

        if ($repository instanceof UserProviderInterface) {
            $refreshedUser = $repository->refreshUser($user);
        } else {
            // The user must be reloaded via the primary key as all other data
            // might have changed without proper persistence in the database.
            // That's the case when the user has been changed by a form with
            // validation errors.
            $id = $this->getClassMetadata()->getIdentifierValues($user);
            if ([] === $id) {
                throw new InvalidArgumentException(
                    'You cannot refresh a user from the EntityUserProvider that does not contain an identifier.'
                    . ' The user object has to be serialized with its own identifier mapped by Doctrine.',
                );
            }

            /** @var UserInterface|null $refreshedUser */
            $refreshedUser = $repository->find($id);

            if (null === $refreshedUser) {
                $serializedId = json_encode($id, JSON_THROW_ON_ERROR);
                $e = new UserNotFoundException('User with id ' . $serializedId . ' not found.');
                $e->setUserIdentifier($serializedId);

                throw $e;
            }
        }

        if ($refreshedUser instanceof Proxy && !$refreshedUser->__isInitialized()) {
            $refreshedUser->__load();
        }

        if ($user instanceof SamlUserInterface && $refreshedUser instanceof SamlUserInterface) {
            $refreshedUser->setSamlAttributes($user->getSamlAttributes());
        }

        $this->refreshRole($refreshedUser);

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return $class === $this->getClass() || is_subclass_of($class, $this->getClass());
    }

    public function getObjectManager(): ObjectManager
    {
        return $this->registry->getManager($this->managerName);
    }

    public function getRepository(): ObjectRepository
    {
        return $this->getObjectManager()->getRepository($this->classOrAlias);
    }

    public function getClass(): string
    {
        if (!isset($this->class)) {
            $class = $this->classOrAlias;

            if (str_contains($class, ':')) {
                $class = $this->getClassMetadata()->getName();
            }

            $this->class = $class;
        }

        return $this->class;
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->getObjectManager()->getClassMetadata($this->classOrAlias);
    }

    public function loadUserByProperty(string $property, string $propertyValue): UserInterface
    {
        $repository = $this->getRepository();

        if (null !== $this->property) {
            if (!$repository instanceof EntityRepository) {
                throw new InvalidArgumentException(
                    \sprintf(
                        'Repository for "%s" must be an instance of "%s".',
                        $this->classOrAlias,
                        EntityRepository::class,
                    ),
                );
            }

            $user = $this->findUser($repository, $property, $propertyValue);
        } else {
            if (!$repository instanceof UserLoaderInterface) {
                throw new InvalidArgumentException(
                    \sprintf(
                        'You must either make the "%s" entity Doctrine Repository ("%s") implement "%s" or set'
                        . ' the "property" option in the corresponding entity provider configuration.',
                        $this->classOrAlias,
                        get_debug_type($repository),
                        UserLoaderInterface::class,
                    ),
                );
            }

            $user = $repository->loadUserByIdentifier($propertyValue);
        }

        if (null === $user) {
            $e = new UserNotFoundException(\sprintf('User "%s" not found.', $propertyValue));
            $e->setUserIdentifier($propertyValue);

            throw $e;
        }

        $this->refreshRole($user);

        return $user;
    }

    /**
     * @param EntityRepository<UserInterface> $repository
     */
    private function findUser(EntityRepository $repository, string $property, string $propertyValue): ?UserInterface
    {
        if (!$this->caseInsensitive) {
            /** @var ?UserInterface $result */
            $result = $repository->findOneBy([$property => $propertyValue]);

            return $result;
        }

        $qb = $repository->createQueryBuilder('o');

        return $qb
            ->andWhere(\sprintf('LOWER(o.%s) = LOWER(:value)', $property))
            ->setParameter(':value', $propertyValue)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
