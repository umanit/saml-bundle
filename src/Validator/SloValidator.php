<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Error\LightSamlValidationException;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\StatusResponse;
use Psr\Log\LoggerInterface;

class SloValidator implements SloValidatorInterface
{
    public function __construct(
        protected SignatureValidatorInterface $signatureValidator,
        protected LoggerInterface $logger
    ) {
    }

    public function validate(string $provider, LogoutResponse $samlMessage, bool $strict = true): void
    {

        $this->logger->info('Validating response status');
        $this->validateStatus($samlMessage);

        $this->signatureValidator->validate($provider, $samlMessage);
    }

    protected function validateStatus(StatusResponse $samlMessage): void
    {
        $status = $samlMessage->getStatus();

        if (!$status->isSuccess()) {
            throw new LightSamlValidationException('Response is not successful');
        }
    }
}
