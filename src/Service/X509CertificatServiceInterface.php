<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Credential\X509Credential;
use LightSaml\Model\XmlDSig\SignatureWriter;

interface X509CertificatServiceInterface
{
    public function getOwnSignature(string $provider): SignatureWriter;

    public function getSpCredential(string $provider): ?X509Credential;

    public function getIdpCredential(string $provider): ?X509Credential;

    /**
     * @param array<string, mixed> $config
     */
    public function getX509Credentials(array $config): ?X509Credential;

    public function getSignature(X509Credential $credential, string $algorithmSignature): SignatureWriter;
}
