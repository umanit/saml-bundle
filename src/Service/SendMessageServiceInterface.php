<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Model\Protocol\SamlMessage;

interface SendMessageServiceInterface
{
    public function send(string $provider, SamlMessage $message, string $bindingType);
}
