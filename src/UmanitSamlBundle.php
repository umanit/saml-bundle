<?php

declare(strict_types=1);

namespace Umanit\SamlBundle;

use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Umanit\SamlBundle\DependencyInjection\Security\Factory\SamlFactory;
use Umanit\SamlBundle\DependencyInjection\Security\UserProvider\SamlUserProviderFactory;

class UmanitSamlBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $extension = $container->getExtension('security');

        if ($extension instanceof SecurityExtension) {
            $extension->addAuthenticatorFactory(new SamlFactory());
            $extension->addUserProviderFactory(new SamlUserProviderFactory());
        }
    }
}
