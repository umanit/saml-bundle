<?php

declare(strict_types=1);

namespace Unit\Service\SpMetadataService;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Service\ConfigurationService;
use Umanit\SamlBundle\Service\MetadataService;
use Unit\Service\MetadataServiceTrait;

class SpMetadataServiceExceptionTest extends TestCase
{
    use MetadataServiceTrait;

    #[DataProvider('getEntityDescriptorExceptionDataProvider')]
    public function testGetEntityDescriptorException(string $provider, array $config, string $expected): void
    {
        $spMetadataService = new MetadataService(
            new ConfigurationService($config),
            $this->getMockUrlGenerator(),
            $this->getMockRouter(),
            $this->getX509Service(),
            new MockHttpClient(
                new MockResponse(
                    '',
                    [
                        'http_code' => Response::HTTP_OK,
                    ],
                ),
            ),
            new NullAdapter(),
            new NullLogger(),
        );

        $this->expectException($expected);

        $spMetadataService->getEntityDescriptor($provider);
    }

    public static function getEntityDescriptorExceptionDataProvider(): array
    {
        $dataset = [];

        $dataset[] = [
            'provider' => 'test',
            'config'   => [
                'providers' => [
                    'test' => [
                        'type' => Mode::IDP_INITIATED,
                        'sp'   => [
                            'entity_id' => 'https://test-entity-id.wip',
                        ],
                    ],
                ],
            ],
            'expected' => RuntimeException::class,
        ];

        $dataset[] = [
            'provider' => 'test_1',
            'config'   => [
                'providers' => [
                    'test_2' => [
                    ],
                ],
            ],
            'expected' => RuntimeException::class,
        ];

        return $dataset;
    }
}
