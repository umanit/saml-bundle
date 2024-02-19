<?php

declare(strict_types=1);

namespace Unit\Service;

use LightSaml\Model\Metadata\ContactPerson;
use LightSaml\Model\Metadata\Organization;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Umanit\SamlBundle\Service\ConfigurationService;
use Umanit\SamlBundle\Service\IdpMetadataService;

/**
 * @group unit
 */
class IdpMetadataServiceTest extends TestCase
{
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
                        'idp' => [
                            'metadata' => 'https://idp.identityserver',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'contactPerson' => $contactPerson,
                'organization' => $organization,
                'entityId' => 'https://idp.identityserver',
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
                    'test' => [
                        'idp' => [
                            'metadata' => 'https://idp.identityserver',
                        ],
                    ],
                    'test2' => [
                        'idp' => [
                            'metadata' => 'https://idp2.identityserver',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'contactPerson' => $contactPerson,
                'organization' => $organization,
                'entityId' => 'https://idp2.identityserver',
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
                    'test' => [
                        'idp' => [
                            'metadata' => self::getMetadata(
                                'https://idp.identityserver',
                                $contactPerson,
                                $organization
                            ),
                        ],
                    ],
                    'test2' => [
                        'idp' => [
                            'metadata' => 'https://idp2.identityserver',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'contactPerson' => $contactPerson,
                'organization' => $organization,
                'entityId' => 'https://idp.identityserver',
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
        ?array $expected
    ): void {
        $configurationService = new ConfigurationService($config);

        $mockedHttpClient = new MockHttpClient(
            new MockResponse(
                self::getMetadata(
                    $expected['entityId'],
                    $expected['contactPerson'],
                    $expected['organization']
                ),
                [
                    'http_code' => Response::HTTP_OK,
                ]
            )
        );

        $idpMetadataService = new IdpMetadataService(
            $configurationService,
            $mockedHttpClient,
            new NullAdapter()
        );

        $result = $idpMetadataService->getEntityDescriptor($provider);

        $this->assertEquals($expected['organization'], $result->getFirstOrganization());
        $this->assertEquals($expected['contactPerson'], $result->getFirstContactPerson());
        $this->assertEquals($expected['entityId'], $result->getEntityID());
    }

    protected static function getMetadata(
        string $entityId,
        ContactPerson $contactPerson,
        Organization $organization
    ): string {
        return <<<XML
<EntityDescriptor
    ID="_c066524f-ba36-49d5-9dfa-ae14e13c1392"
    entityID="$entityId"
    validUntil="2022-07-20T09:48:54Z"
    cacheDuration="PT15M"
    xmlns="urn:oasis:names:tc:SAML:2.0:metadata"
    xmlns:saml2="urn:oasis:names:tc:SAML:2.0:assertion">

    <IDPSSODescriptor WantAuthnRequestsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://idp.identityserver/saml/sso" />
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://idp.identityserver/saml/sso" />
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://idp.identityserver/saml/sso" />

        <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://idp.identityserver/saml/slo" />
        <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://idp.identityserver/saml/slo" />
        <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://idp.identityserver/saml/slo" />

        <ArtifactResolutionService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://idp.identityserver/saml/ars" index="0" />

        <NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</NameIDFormat>
        <NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:persistent</NameIDFormat>

        <KeyDescriptor use="signing">
            <KeyInfo
                xmlns="http://www.w3.org/2000/09/xmldsig#">
                <X509Data>
                    <X509Certificate>
MIIDazCCAlOgAwIBAgIUHSsJhP8f1ZJQFmDePRHSah6maccwDQYJKoZIhvcNAQEL
BQAwRTELMAkGA1UEBhMCQVUxEzARBgNVBAgMClNvbWUtU3RhdGUxITAfBgNVBAoM
GEludGVybmV0IFdpZGdpdHMgUHR5IEx0ZDAeFw0yNDAyMTUxNTU2NDdaFw0yNTAy
MTQxNTU2NDdaMEUxCzAJBgNVBAYTAkFVMRMwEQYDVQQIDApTb21lLVN0YXRlMSEw
HwYDVQQKDBhJbnRlcm5ldCBXaWRnaXRzIFB0eSBMdGQwggEiMA0GCSqGSIb3DQEB
AQUAA4IBDwAwggEKAoIBAQDJL/2X3RkmtiKy36/plSqWbnDV/LJLKoXOSkMwL8JG
Hybyh+vOQexZHXSXZRVNIzupzUgDSNk+I1iZwL25HLMCpj4NUqg5evWlsFGTtDrZ
mGbFJGEB3VX08o1I+bkHpepfBiE4hGnqGLRPXTbUGHIwHcvmvAAjfzQ2yuJLIeRo
mLpxmRk3Boo50gMOGETsYN/VzieP2RIwfdSA/ofl8S8JqINhCLdt8FkLJJ3XNfrG
kCVrsUlcaXDIvVAy3gDbo2ndshstGwpRodERKg7RRvcMBWTJM4QjduBZVI8osp3t
QhRymPPa8Y5uQAHEdnqhaI5fh4FJ/Oi24TOlUchgEwqhAgMBAAGjUzBRMB0GA1Ud
DgQWBBTT3M4Kk5GfCWIrMF/kKz/Lpp8eBTAfBgNVHSMEGDAWgBTT3M4Kk5GfCWIr
MF/kKz/Lpp8eBTAPBgNVHRMBAf8EBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQCd
N7e+rrRWlV8uq5AAgmGDHuXQzTWmG2l+8Q9OWQGogZm87eQBP9uuhQoAZLCyoVAw
MTRKVH9Y9lc7TPb+786YdfK/3l3S2YJHYt/vn1oi8Zv5XVd6yxX2FCJxsoByOw2k
aX/xvoGWZ6tQ6o6g1Xnu4S+rzFayvS5Qr+eQ3oUjOnhhXojVaGNU41ZYAPHPIADA
vD0adw4PHkYrj9XSuNAAjTNoiPLBMTn3oMt1hQnRxZzxPi7H2GOJO5fFSOcw7Kt4
cQHSRkn3K8nlzziyynIaRAVnbwk382ercL1gyWzbeZ4D0m3UxRtRUGXNSHubkOcu
GdMxXz1iS6Iv8HIZdyUT
</X509Certificate>
                </X509Data>
            </KeyInfo>
        </KeyDescriptor>
    </IDPSSODescriptor>

    <Organization>
        <OrganizationName xml:lang="en-US">{$organization->getOrganizationName()}</OrganizationName>
        <OrganizationDisplayName xml:lang="en-US">{$organization->getOrganizationDisplayName()}</OrganizationDisplayName>
        <OrganizationURL xml:lang="en-US">{$organization->getOrganizationURL()}</OrganizationURL>
    </Organization>

    <ContactPerson contactType="{$contactPerson->getContactType()}">
        <Company>{$contactPerson->getCompany()}</Company>
        <GivenName>{$contactPerson->getGivenName()}</GivenName>
        <SurName>{$contactPerson->getSurName()}</SurName>
        <EmailAddress>{$contactPerson->getEmailAddress()}</EmailAddress>
    </ContactPerson>

</EntityDescriptor>
XML;
    }
}
