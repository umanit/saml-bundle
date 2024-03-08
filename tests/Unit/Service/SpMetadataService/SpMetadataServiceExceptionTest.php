<?php

declare(strict_types=1);

namespace Unit\Service\SpMetadataService;

use PHPUnit\Framework\TestCase;
use Umanit\SamlBundle\Service\ConfigurationService;
use Umanit\SamlBundle\Service\SpMetadataService;

class SpMetadataServiceExceptionTest extends TestCase
{
    use SpMetadataServiceTrait;

    public static function getEntityDescriptorExceptionDataProvider(): array
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
            'expected' => \InvalidArgumentException::class,
        ];

        $dataset[] = [
            'provider' => 'test_1',
            'config'   => [
                'providers' => [
                    'test_2' => [
                    ],
                ],
            ],
            'expected' => \RuntimeException::class,
        ];

        return $dataset;
    }

    /**
     * @dataProvider getEntityDescriptorExceptionDataProvider
     */
    public function testGetEntityDescriptorException(
        string $provider,
        array $config,
        string $expected
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

        $this->expectException($expected);

        $spMetadataService->getEntityDescriptor($provider);
    }
}
