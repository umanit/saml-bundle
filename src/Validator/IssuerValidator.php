<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Validator\Model\NameId\NameIdValidatorInterface;

class IssuerValidator implements IssuerValidatorInterface
{
    public function __construct(
        protected readonly NameIdValidatorInterface $nameIdValidator
    ) {
    }

    public function validate(SamlMessage $samlMessage): void
    {
        $issuer = $samlMessage->getIssuer();

        if (null === $issuer) {
            throw new \LogicException('Issuer is missing');
        }

        if (null === $issuer->getValue()) {
            throw new \LogicException('Issuer value is missing');
        }

        $this->nameIdValidator->validateNameId($issuer);
    }
}
