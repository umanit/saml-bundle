<?php

declare(strict_types=1);

namespace Unit\Service;

use PHPUnit\Framework\TestCase;
use RuntimeException;
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
            'config'   => [
                'providers' => [
                    'test' => [
                        'test' => 'test',
                    ],
                ],
            ],
            'expected' => ['test' => 'test'],
        ];

        # Dataset 2
        $dataset[] = [
            'provider' => 'test2',
            'config'   => [
                'providers' => [
                    'test2' => [
                        'test' => 'test',
                    ],
                    'test3' => [
                        'test' => 'test',
                    ],
                ],
            ],
            'expected' => ['test' => 'test'],
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

    public static function getProviderNamesDataProvider(): array
    {
        $dataset = [];

        # Dataset 0
        $dataset[] = [
            'config'   => [
                'providers' => [
                    'test' => [],
                ],
            ],
            'expected' => ['test'],
        ];

        # Dataset 1
        $dataset[] = [
            'config'   => [
                'providers' => [
                    'test1' => [],
                    'test2' => [],
                ],
            ],
            'expected' => ['test1', 'test2'],
        ];

        return $dataset;
    }

    /**
     * @dataProvider getProviderNamesDataProvider
     */
    public function testGetProviderNames(array $config, array $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getProviderNames());
    }

    public static function getByProviderExceptionDataProvider(): array
    {
        $dataset = [];

        # Dataset 0
        $dataset[] = [
            'provider' => 'test',
            'config'   => [
                'providers' => [
                    'KO' => [],
                ],
            ],
            'expected' => RuntimeException::class,
        ];

        # Dataset 1
        $dataset[] = [
            'provider' => 'test2',
            'config'   => [
                'providers' => [],
            ],
            'expected' => RuntimeException::class,
        ];

        return $dataset;
    }

    /**
     * @dataProvider getByProviderExceptionDataProvider
     */
    public function testGetByProviderException(string $provider, array $config, string $expected): void
    {
        $this->expectException($expected);
        $configurationService = new ConfigurationService($config);
        $configurationService->getByProvider($provider);
    }

    public static function getRedirectTemplateDataProvider(): array
    {
        $dataset[] = [
            'config'   => [
                'twig_templates' => [
                    'redirect' => '@UmanitSaml/redirect.html.twig',
                ]
            ],
            'expected' => '@UmanitSaml/redirect.html.twig',
        ];

        return $dataset;
    }

    /**
     * @dataProvider getRedirectTemplateDataProvider
     */
    public function testGetRedirectionTemplate(array $config, string $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getRedirectTemplate());
    }
}
