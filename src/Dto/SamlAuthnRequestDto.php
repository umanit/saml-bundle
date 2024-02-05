<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Dto;

readonly class SamlAuthnRequestDto
{
    public function __construct(
        private string $destinationUrl,
        private string $base64,
    ) {
    }

    public function getDestinationUrl(): string
    {
        return $this->destinationUrl;
    }

    public function getBase64(): string
    {
        return $this->base64;
    }
}
