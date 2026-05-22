<?php

declare(strict_types=1);

namespace Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Umanit\SamlBundle\Service\ConfigurationService;

#[Group('unit')]
class ConfigurationTest extends TestCase
{
    #[DataProvider('getByProviderDataProvider')]
    public function testGetByProvider(string $provider, array $config, ?array $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getByProvider($provider));
    }

    #[DataProvider('getProviderNamesDataProvider')]
    public function testGetProviderNames(array $config, array $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getProviderNames());
    }

    #[DataProvider('getByProviderExceptionDataProvider')]
    public function testGetByProviderException(string $provider, array $config, string $expected): void
    {
        $this->expectException($expected);
        $configurationService = new ConfigurationService($config);
        $configurationService->getByProvider($provider);
    }

    #[DataProvider('getRedirectTemplateDataProvider')]
    public function testGetRedirectionTemplate(array $config, string $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getRedirectTemplate());
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

    public static function getRedirectTemplateDataProvider(): array
    {
        $dataset[] = [
            'config'   => [
                'twig_templates' => [
                    'redirect' => '@UmanitSaml/redirect.html.twig',
                ],
            ],
            'expected' => '@UmanitSaml/redirect.html.twig',
        ];

        return $dataset;
    }
}
