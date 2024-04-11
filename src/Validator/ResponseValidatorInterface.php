<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Model\Protocol\Response;

interface ResponseValidatorInterface
{
    public function validate(string $provider, Response $samlMessage, bool $strict = true): void;
}
