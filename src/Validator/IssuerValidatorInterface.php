<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Model\Protocol\SamlMessage;

interface IssuerValidatorInterface
{
    public function validate(SamlMessage $samlMessage): void;
}
