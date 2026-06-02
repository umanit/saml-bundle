<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Exception;

class ProviderNotFoundException extends \InvalidArgumentException
{
    public function __construct(string $provider)
    {
        parent::__construct(\sprintf('Provider "%s" not found', $provider));
    }
}
