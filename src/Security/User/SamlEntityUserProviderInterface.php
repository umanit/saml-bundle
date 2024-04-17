<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\User\UserInterface;

interface SamlEntityUserProviderInterface extends SamlUserProviderInterface
{
    public function getObjectManager(): ObjectManager;

    /**
     * @return ObjectRepository<UserInterface>
     */
    public function getRepository(): ObjectRepository;

    public function getClass(): string;

    public function getClassMetadata(): ClassMetadata;

    public function loadUserByProperty(string $property, string $propertyValue): UserInterface;
}
