<?php

declare(strict_types=1);

namespace Unit\Service;

use LightSaml\Model\Metadata\ContactPerson;
use LightSaml\Model\Metadata\Organization;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Service\ConfigurationService;
use Umanit\SamlBundle\Service\MetadataService;

#[Group('unit')]
class MetadataServiceTest extends TestCase
{
    use MetadataServiceTrait;

    #[DataProvider('getEntityDescriptorDataProvider')]
    public function testGetEntityDescriptor(string $provider, array $config, ?array $expected): void
    {
        $metadataService = new MetadataService(
            new ConfigurationService($config),
            $this->getMockUrlGenerator(),
            $this->getMockRouter(),
            $this->getX509Service(),
            new MockHttpClient(
                new MockResponse(
                    self::getMetadata(
                        $expected['entityId'],
                        $expected['contactPerson'],
                        $expected['organization'],
                    ),
                    [
                        'http_code' => Response::HTTP_OK,
                    ],
                ),
            ),
            new NullAdapter(),
            new NullLogger(),
        );

        $result = $metadataService->getEntityDescriptor($provider);

        $this->assertEquals($expected['organization'], $result->getFirstOrganization());
        $this->assertEquals($expected['contactPerson'], $result->getFirstContactPerson());
        $this->assertEquals($expected['entityId'], $result->getEntityID());
    }

    public static function getEntityDescriptorDataProvider(): array
    {
        $dataset = [];

        # Dataset 1
        $contactPerson = new ContactPerson();
        $contactPerson->setContactType('technical');
        $contactPerson->setCompany('Example');
        $contactPerson->setGivenName('bob');
        $contactPerson->setSurName('smith');
        $contactPerson->setEmailAddress('bob@example.com');

        $organization = new Organization();
        $organization->setOrganizationName('Example');
        $organization->setOrganizationDisplayName('Example Org');
        $organization->setOrganizationURL('https://example.com/');
        $organization->setLang('en-US');

        $dataset[] = [
            'provider' => 'test',
            'config'   => [
                'providers' => [
                    'test' => [
                        'type' => Mode::SP_INITIATED,
                        'idp'  => [
                            'metadata' => 'https://idp.identityserver',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'contactPerson' => $contactPerson,
                'organization'  => $organization,
                'entityId'      => 'https://idp.identityserver',
            ],
        ];

        # Dataset 1
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

        $dataset[] = [
            'provider' => 'test2',
            'config'   => [
                'providers' => [
                    'test'  => [
                        'type' => Mode::SP_INITIATED,
                        'idp'  => [
                            'metadata' => 'https://idp.identityserver',
                        ],
                    ],
                    'test2' => [
                        'type' => Mode::SP_INITIATED,
                        'idp'  => [
                            'metadata' => 'https://idp2.identityserver',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'contactPerson' => $contactPerson,
                'organization'  => $organization,
                'entityId'      => 'https://idp2.identityserver',
            ],
        ];

        # Dataset  2 : Load from string
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

        $dataset[] = [
            'provider' => 'test',
            'config'   => [
                'providers' => [
                    'test'  => [
                        'type' => Mode::SP_INITIATED,
                        'idp'  => [
                            'metadata' => self::getMetadata(
                                'https://idp.identityserver',
                                $contactPerson,
                                $organization,
                            ),
                        ],
                    ],
                    'test2' => [
                        'type' => Mode::SP_INITIATED,
                        'idp'  => [
                            'metadata' => 'https://idp2.identityserver',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'contactPerson' => $contactPerson,
                'organization'  => $organization,
                'entityId'      => 'https://idp.identityserver',
            ],
        ];

        return $dataset;
    }
}
