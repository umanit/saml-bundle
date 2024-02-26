<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Guesser;

use Symfony\Component\HttpFoundation\Request;

interface BestContentTypeGuesserInterface
{
    public const SUPPORTED_METADATA_TYPES = ['application/samlmetadata+xml', 'application/xml', 'text/xml'];

    public function guessForMetadataRequest(Request $request, string $contentType = 'text/xml'): string;
}
