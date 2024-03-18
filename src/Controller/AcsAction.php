<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[Route('acs/{provider<\w+>}', name: 'umanit_saml_acs', methods: ['GET', 'POST'])]
class AcsAction implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    public function __invoke(string $provider): Response {
        throw new \LogicException('Method not implemented');
    }

    #[Required]
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = $this->container ?? null;
        $this->container = $container;

        return $previous;
    }

    public static function getSubscribedServices(): array
    {
        return [];
    }
}
