<?php

declare(strict_types=1);

namespace Unit\Service\SpMetadataService;

use LightSaml\Model\Metadata\ContactPerson;
use LightSaml\Model\Metadata\Organization;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Service\ConfigurationService;
use Umanit\SamlBundle\Service\MetadataService;
use Unit\Service\MetadataServiceTrait;

class SpMetadataServiceTest extends TestCase
{
    use MetadataServiceTrait;

    #[DataProvider('getEntityDescriptorDataProvider')]
    public function testGetEntityDescriptor(
        string $provider,
        array $config,
        array $expected,
    ): void {
        $contactPerson = new ContactPerson();
        $contactPerson->setContactType('test2');
        $contactPerson->setCompany('test2');
        $contactPerson->setGivenName('test2');
        $contactPerson->setSurName('test2');
        $contactPerson->setEmailAddress('test2@example.com');

        $organization = new Organization();
        $organization->setOrganizationName('test2');
        $organization->setOrganizationDisplayName('test2 Org');
        $organization->setOrganizationURL('https://test2.com/');
        $organization->setLang('en-US');

        $spMetadataService = new MetadataService(
            new ConfigurationService($config),
            $this->getMockUrlGenerator(),
            $this->getMockRouter(),
            $this->getX509Service(),
            new MockHttpClient(
                new MockResponse(
                    self::getMetadata(
                        $expected['entity_id'],
                        $contactPerson,
                        $organization,
                    ),
                    [
                        'http_code' => Response::HTTP_OK,
                    ],
                ),
            ),
            new NullAdapter(),
            new NullLogger(),
        );

        $result = $spMetadataService->getOwnEntityDescriptor($provider);

        $this->assertEquals($expected['entity_id'], $result->getEntityID());
        $this->assertEquals(
            $expected['acs']['url'],
            $result->getFirstSpSsoDescriptor()->getFirstAssertionConsumerService()->getLocation(),
        );
        $this->assertEquals(
            $expected['slo']['url'],
            $result->getFirstSpSsoDescriptor()->getFirstSingleLogoutService()->getLocation(),
        );
    }

    public static function getEntityDescriptorDataProvider(): array
    {
        $dataset = [];

        $dataset[] = [
            'provider' => 'test',
            'config'   => [
                'providers' => [
                    'test' => [
                        'type' => Mode::SP_INITIATED,
                        'sp'   => [
                            'entity_id' => 'https://test-entity-id.wip',
                            'acs'       => [
                                'url' => 'https://saml-bundle.wip/saml2/acs/microsoft_umanit_provider',
                            ],
                            'slo'       => [
                                'url' => 'https://saml-bundle.wip/saml2/slo/microsoft_umanit_provider',
                            ],
                        ],
                        'idp'  => [
                            'metadata' => 'https://idp.identityserver',
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
}
