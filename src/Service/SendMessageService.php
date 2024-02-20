<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\SamlMessage;

class SendMessageService implements SendMessageServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
    ) {
    }

    public function send(string $provider, SamlMessage $message, string $bindingType)
    {
        $config = $this->configurationService->getByProvider($provider);
    }
}
