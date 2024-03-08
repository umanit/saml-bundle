<?php

declare(strict_types=1);

namespace Unit\Service\SpMetadataService;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Umanit\SamlBundle\Service\X509CertificatServiceInterface;

trait SpMetadataServiceTrait
{
    public function getMockUrlGenerator(): UrlGeneratorInterface
    {
        return $this->getMockBuilder(UrlGeneratorInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock()
        ;
    }

    public function getMockRouter(): RouterInterface
    {
        return $this->getMockBuilder(RouterInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock()
        ;
    }

    public function getX509Service(): X509CertificatServiceInterface
    {
        return $this->getMockBuilder(X509CertificatServiceInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock()
        ;
    }
}
