<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Model\Assertion\Assertion;

interface AssertionValidatorInterface
{
    public function validateAssertion(Assertion $assertion): void;
}
