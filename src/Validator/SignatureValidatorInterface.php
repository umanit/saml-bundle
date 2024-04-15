<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Model\Protocol\SamlMessage;

interface SignatureValidatorInterface
{
    public function validate(string $provider, SamlMessage $message): void;
}
