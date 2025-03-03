<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Event;

class BeforeSamlResponseEvent
{
    public function __construct(
        public string $provider,
        public ?string $nameIdFormat = null,
        public array $attributes = [],
    ) {
    }
}
