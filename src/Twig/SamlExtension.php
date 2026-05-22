<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Twig;

use Twig\Attribute\AsTwigFunction;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Serializer\SamlElementSerializerInterface;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\SamlResponseServiceInterface;

final readonly class SamlExtension
{
    public function __construct(
        private ConfigurationServiceInterface $configurationService,
        private SamlResponseServiceInterface $samlResponseService,
        private SamlElementSerializerInterface $samlElementSerializer,
    ) {
    }

    /**
     * @param array<string, string> $attributes
     *
     * @return ?array{
     *     data: string,
     *     url: string,
     * }
     */
    #[AsTwigFunction(name: 'generate_saml_response')]
    public function generateSamlResponse(string $provider, string $nameIdFormat, array $attributes = []): ?array
    {
        try {
            $config = $this->configurationService->getByProvider($provider);

            if (Mode::IDP_INITIATED !== $config['type']) {
                throw new \RuntimeException('Invalid provider type');
            }

            $response = $this->samlResponseService->getSamlResponse($provider, $nameIdFormat, $attributes);
            $xml = $this->samlElementSerializer->toXml($response);
        } catch (\Throwable) {
            return null;
        }

        return [
            'data' => base64_encode($xml),
            'url'  => $response->getDestination(),
        ];
    }
}
