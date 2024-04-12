<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

use LightSaml\SamlConstants;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;

class SamlScopedUserProvider implements SamlScopedUserProviderInterface
{
    /**
     * @param array<string, UserProviderInterface> $userProviders
     */
    public function __construct(
        private readonly array $userProviders,
        private readonly ConfigurationServiceInterface $configurationService
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return !is_a($class, UserInterface::class, true);
    }

    public function loadUserByIdentifierAndProvider(string $identifier, string $provider, array $attributes = []): UserInterface
    {
        $nameIdFormat = $this->configurationService->getNameIdFormat($provider);

        $provider = $this->userProviders[$provider] ?? null;

        if (null === $provider) {
            throw new \RuntimeException(sprintf('No user provider found for provider "%s"', $provider));
        }

        if ($nameIdFormat === SamlConstants::NAME_ID_FORMAT_EMAIL && $provider instanceof SamlUserProviderInterface) {
            $user = $provider->loadUserByEmail($identifier);
        }

        $user = $provider->loadUserByIdentifier($identifier);

        if ($user instanceof SamlUserInterface) {
            $user->setSamlAttributes($attributes);
        }

        if ($provider instanceof SamlEntityUserProvider) {
            $provider->refreshRole($user);
        }

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        throw new \RuntimeException('You must provide a provider to load a user by identifier');
    }
}
