<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\Authenticator\Token;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class SamlToken extends PostAuthenticationToken
{
    /**
     * @param array<string> $roles
     */
    public function __construct(
        UserInterface $user,
        string $firewallName,
        array $roles,
        array $samlAttributes,
        private string $providerKey = 'saml'
    ) {
        parent::__construct($user, $firewallName, $roles);

        $this->setAttributes($samlAttributes);
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }

    public function setProviderKey(string $providerKey): void
    {
        $this->providerKey = $providerKey;
    }

    public function __serialize(): array
    {
        return [$this->providerKey, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        [$this->providerKey, $parentData] = $data;
        parent::__unserialize($parentData);
    }
}
