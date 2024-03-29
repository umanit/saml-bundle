<?php

declare(strict_types=1);

namespace Unit\Service\SpMetadataService;

use PHPUnit\Framework\TestCase;
use Umanit\SamlBundle\Service\ConfigurationService;
use Umanit\SamlBundle\Service\OwnMetadataService;

class SpMetadataServiceTest extends TestCase
{
    use SpMetadataServiceTrait;

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
                            'acs'       => [
                                'url' => 'https://saml-bundle.wip/saml2/acs/microsoft_umanit_provider',
                            ],
                            'slo'       => [
                                'url' => 'https://saml-bundle.wip/saml2/slo/microsoft_umanit_provider',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'entity_id' => 'https://test-entity-id.wip',
                'acs'       => [
                    'url' => 'https://saml-bundle.wip/saml2/acs/microsoft_umanit_provider',
                ],
                'slo'       => [
                    'url' => 'https://saml-bundle.wip/saml2/slo/microsoft_umanit_provider',
                ],
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

        $spMetadataService = new OwnMetadataService(
            $configurationService,
            $urlGenerator,
            $router,
            $X509CertificatService
        );

        $result = $spMetadataService->getEntityDescriptor($provider);

        $this->assertEquals($expected['entity_id'], $result->getEntityID());
        $this->assertEquals(
            $expected['acs']['url'],
            $result->getFirstSpSsoDescriptor()->getFirstAssertionConsumerService()->getLocation()
        );
        $this->assertEquals(
            $expected['slo']['url'],
            $result->getFirstSpSsoDescriptor()->getFirstSingleLogoutService()->getLocation()
        );
    }
}
