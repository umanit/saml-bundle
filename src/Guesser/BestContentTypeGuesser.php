<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Guesser;

use Symfony\Component\HttpFoundation\Request;

class BestContentTypeGuesser implements BestContentTypeGuesserInterface
{
    public function guessForMetadataRequest(Request $request, string $contentType = 'text/xml'): string
    {
        $acceptableContentTypes = array_flip($request->getAcceptableContentTypes());

        foreach (self::SUPPORTED_METADATA_TYPES as $supportedContentType) {
            if (isset($acceptableContentTypes[$supportedContentType])) {
                return $supportedContentType;
            }
        }

        return $contentType;
    }
}
