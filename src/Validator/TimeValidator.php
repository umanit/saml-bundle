<?php

namespace Umanit\SamlBundle\Validator;

use LightSaml\Model\Assertion\Assertion;
use LightSaml\Provider\TimeProvider\TimeProviderInterface;
use LightSaml\Validator\Model\Assertion\AssertionTimeValidatorInterface;

class TimeValidator implements TimeValidatorInterface
{
    public function __construct(
        protected AssertionTimeValidatorInterface $assertionTimeValidator,
        protected TimeProviderInterface $timeProvider,
        protected int $allowedSecondsSkew = 120
    ) {
    }

    public function validateAssertion(Assertion $assertion): void
    {
        $this->assertionTimeValidator->validateTimeRestrictions(
            $assertion,
            $this->timeProvider->getTimestamp(),
            $this->allowedSecondsSkew
        );
    }
}
