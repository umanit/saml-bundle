<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Exception;

class ProviderDisabledException extends \RuntimeException
{
    public function __construct(string $provider)
    {
        parent::__construct(\sprintf('Provider "%s" is disabled.', $provider));
    }
}
