<?php

declare(strict_types=1);

namespace Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Umanit\SamlBundle\Service\ConfigurationService;

#[Group('unit')]
final class ConfigurationTest extends TestCase
{
    /**
     * @param array<string, array<string, array<string, string>>> $config
     * @param ?array<string, mixed>                               $expected
     */
    #[DataProvider('getByProviderDataProvider')]
    public function testGetByProvider(string $provider, array $config, ?array $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getByProvider($provider));
    }

    /**
     * @param array<string, array<string, list<mixed>>> $config
     * @param list<string>                              $expected
     */
    #[DataProvider('getProviderNamesDataProvider')]
    public function testGetProviderNames(array $config, array $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getProviderNames());
    }

    /**
     * @param array<string, array<string, list<mixed>>> $config
     */
    #[DataProvider('getByProviderExceptionDataProvider')]
    public function testGetByProviderException(string $provider, array $config, string $expected): void
    {
        $this->expectException($expected);
        $configurationService = new ConfigurationService($config);
        $configurationService->getByProvider($provider);
    }

    /**
     * @param array<string, array<string, list<mixed>>> $config
     */
    #[DataProvider('getRedirectTemplateDataProvider')]
    public function testGetRedirectionTemplate(array $config, string $expected): void
    {
        $configurationService = new ConfigurationService($config);
        $this->assertSame($expected, $configurationService->getRedirectTemplate());
    }

    /**
     * @return \Iterator<int, array<string, mixed>>
     */
    public static function getProviderNamesDataProvider(): \Iterator
    {
        yield [
            'config'   => [
                'providers' => [
                    'test' => [],
                ],
            ],
            'expected' => ['test'],
        ];

        yield [
            'config'   => [
                'providers' => [
                    'test1' => [],
                    'test2' => [],
                ],
            ],
            'expected' => ['test1', 'test2'],
        ];
    }

    /**
     * @return \Iterator<int, array<string, mixed>>
     */
    public static function getByProviderExceptionDataProvider(): \Iterator
    {
        yield [
            'provider' => 'test',
            'config'   => [
                'providers' => [
                    'KO' => [],
                ],
            ],
            'expected' => RuntimeException::class,
        ];

        yield [
            'provider' => 'test2',
            'config'   => [
                'providers' => [],
            ],
            'expected' => RuntimeException::class,
        ];
    }

    /**
     * @return \Iterator<int, array<string, mixed>>
     */
    public static function getByProviderDataProvider(): \Iterator
    {
        yield [
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

        yield [
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
    }

    /**
     * @return \Iterator<int, array<string, mixed>>
     */
    public static function getRedirectTemplateDataProvider(): \Iterator
    {
        yield [
            'config'   => [
                'twig_templates' => [
                    'redirect' => '@UmanitSaml/redirect.html.twig',
                ],
            ],
            'expected' => '@UmanitSaml/redirect.html.twig',
        ];
    }
}
