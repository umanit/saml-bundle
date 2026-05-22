<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Event;

final readonly class BeforeSamlResponseEvent
{
    public function __construct(
        public string $provider,
        public ?string $nameIdFormat = null,
        /** @var array<string, mixed> */
        public array $attributes = [],
    ) {
    }
}
