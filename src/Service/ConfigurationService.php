<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\SamlConstants;
use Umanit\SamlBundle\Enums\Mode;

class ConfigurationService implements ConfigurationServiceInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(protected array $config)
    {
    }

    public function getCertificatePath(): string
    {
        return $this->config['certificat_path'];
    }

    public function getByProvider(string $provider): array
    {
        $config = $this->config['providers'][$provider] ?? null;

        if (empty($config)) {
            throw new \RuntimeException(\sprintf('Provider "%s" not found', $provider));
        }

        if (isset($config['enabled']) && !$config['enabled']) {
            throw new \RuntimeException(\sprintf('Provider "%s" is disabled', $provider));
        }

        return $config;
    }

    public function getProviderNames(array $tags = []): array
    {
        $names = [];

        foreach ($this->config['providers'] as $key => $provider) {
            if (isset($provider['enabled']) && !$provider['enabled']) {
                continue;
            }

            if (!empty($tags) && !array_intersect($tags, $provider['tags'])) {
                continue;
            }

            $names[] = $key;
        }

        return $names;
    }

    public function getNameIdFormat(string $provider): string
    {
        $config = $this->getByProvider($provider);

        if ($config['type'] === Mode::SP_INITIATED) {
            return $config['sp']['name_id_format'] ?? SamlConstants::NAME_ID_FORMAT_UNSPECIFIED;
        }

        return $config['idp']['name_id_format'] ?? SamlConstants::NAME_ID_FORMAT_UNSPECIFIED;
    }

    public function getRedirectTemplate(): string
    {
        return $this->config['twig_templates']['redirect'];
    }
}
