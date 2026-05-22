<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Twig;

use Throwable;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Serializer\SamlElementSerializerInterface;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\SamlResponseServiceInterface;

final class SamlExtension extends AbstractExtension
{
    public function __construct(
        private readonly ConfigurationServiceInterface $configurationService,
        private readonly SamlResponseServiceInterface $samlResponseService,
        private readonly SamlElementSerializerInterface $samlElementSerializer,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('generate_saml_response', $this->generateSamlResponse(...)),
        ];
    }

    /**
     * @param array<string, string> $attributes
     *
     * @return ?array{
     *     data: string,
     *     url: string,
     * }
     */
    public function generateSamlResponse(string $provider, string $nameIdFormat, array $attributes = []): ?array
    {
        try {
            $config = $this->configurationService->getByProvider($provider);

            if (Mode::IDP_INITIATED !== $config['type']) {
                throw new \RuntimeException('Invalid provider type');
            }

            $response = $this->samlResponseService->getSamlResponse($provider, $nameIdFormat, $attributes);
            $xml = $this->samlElementSerializer->toXml($response);
        } catch (Throwable) {
            return null;
        }

        return [
            'data' => base64_encode($xml),
            'url'  => $response->getDestination(),
        ];
    }
}
