<?php

declare(strict_types=1);

namespace Unit\Service\SpMetadataService;

use PHPUnit\Framework\TestCase;
use Umanit\SamlBundle\Service\ConfigurationService;
use Umanit\SamlBundle\Service\SpMetadataService;

class SpMetadataServiceTest extends TestCase
{
    public static function getEntityDescriptorDataProvider(): array
    {
        $dataset = [];

        $dataset[] = [
            'provider' => 'test',
            'config'   => [
                'providers' => [
                    'test' => [
                        'sp' => [
                            'entity_id' => 'https://test-entity-id.wip',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'entity_id' => 'https://test-entity-id.wip',
            ],
        ];

        return $dataset;
    }

    /**
     * @dataProvider getEntityDescriptorDataProvider
     */
    public function testGetEntityDescriptor(
        string $provider,
        array $config,
        array $expected,
    ): void {
        $configurationService = new ConfigurationService($config);
        $urlGenerator = $this->getMockUrlGenerator();
        $router = $this->getMockRouter();
        $X509CertificatService = $this->getX509Service();

        $spMetadataService = new SpMetadataService(
            $configurationService,
            $urlGenerator,
            $router,
            $X509CertificatService
        );

        $result = $spMetadataService->getEntityDescriptor($provider);

        $this->assertEquals($expected['entity_id'], $result->getEntityID());
    }
}
