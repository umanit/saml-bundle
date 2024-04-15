<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

trait UserProviderTrait
{
    public function refreshRole(UserInterface $user): void
    {
        $roles = $user->getRoles();
        $roles = array_merge($roles, $this->defaultRoles);

        if ($user instanceof SamlUserInterface) {
            $attributes = $user->getSamlAttributes();

            foreach ($this->rolesMapping as $config) {
                $attributeName = $config['attribute_name'];

                if (!isset($attributes[$attributeName])) {
                    continue;
                }

                $attribute = $attributes[$attributeName];

                if (is_string($attribute)) {
                    $attribute = explode(';', $attribute);
                }

                if ($config['type'] === 'memberof' && in_array($config['needed'], $attribute, true)) {
                    $roles[] = $config['role'];
                }
            }
        }

        $roles = array_unique($roles);

        if (method_exists($user, 'setRoles')) {
            $user->setRoles($roles);
        }
    }
}
