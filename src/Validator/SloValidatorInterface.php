<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Model\Protocol\LogoutResponse;

interface SloValidatorInterface
{
    public function validate(string $provider, LogoutResponse $samlMessage, bool $strict = true): void;
}
