<?php

declare(strict_types=1);

namespace Unit\Service;

use PHPUnit\Framework\TestCase;
use Umanit\SamlBundle\Service\ConfigurationService;

/**
 * @group unit
 */
class ConfigurationTest extends TestCase
{
    public static function getByProviderDataProvider(): array
    {
        $dataset = [];

        # Dataset 1
        $dataset[] = [
            'provider' => 'test',
            'config' => [
                'providers' => [
                    'test' => []
                ]
            ],
            'expected' => []
        ];

        # Dataset 2
        $dataset[] = [
            'provider' => 'test',
            'config' => [
                'providers' => [
                    'KO' => []
                ]
            ],
            'expected' => null
        ];

        return $dataset;
    }

    /**
     * @dataProvider getByProviderDataProvider
     *
     * @param string     $provider
     * @param array      $config
     * @param array|null $expected
     *
     * @return void
     */
    public function testGetByProvider(string $provider, array $config, ?array $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getByProvider($provider));
    }
}
