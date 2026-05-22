<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Model\XmlDSig\SignatureWriter;
use Umanit\SamlBundle\Enums\Mode;

class X509CertificatService implements X509CertificatServiceInterface
{
    public function __construct(
        protected readonly ConfigurationServiceInterface $configurationService,
    ) {
    }

    public function getOwnSignature(string $provider): SignatureWriter
    {
        $providerConfiguration = $this->configurationService->getByProvider($provider);

        if ($providerConfiguration['type'] === Mode::SP_INITIATED) {
            $configuration = $providerConfiguration['sp'];
        } else {
            $configuration = $providerConfiguration['idp'];
        }

        $ownCredential = $this->getX509Credentials($configuration);

        if (null === $ownCredential) {
            throw new \RuntimeException('Unable to get own credential');
        }

        return $this->getSignature($ownCredential, $configuration['saml_algorithm_signature']->value);
    }

    public function getSpCredential(string $provider): ?X509Credential
    {
        $config = $this->configurationService->getByProvider($provider);

        return $this->getX509Credentials($config['sp']);
    }

    public function getIdpCredential(string $provider): ?X509Credential
    {
        $config = $this->configurationService->getByProvider($provider);

        return $this->getX509Credentials($config['idp']);
    }

    public function getX509Credentials(array $config): ?X509Credential
    {
        $x509Cert = $config['x509cert'] ?? null;
        $privateKey = $config['private_key']['path'] ?? null;
        $privateKeyEncryption = $config['private_key']['encryption']->value ?? null;
        $privateKeyPassphrase = $config['private_key_passphrase'] ?? '';

        if (null === $x509Cert || null === $privateKey) {
            return null;
        }

        $isFile = file_exists($privateKey) && is_readable($privateKey);

        // Laisser la possibilité de gérer le niveau de chiffrement
        return new X509Credential(
            $this->makeCertificate($x509Cert),
            KeyHelper::createPrivateKey(
                $privateKey,
                $privateKeyPassphrase,
                $isFile,
                $privateKeyEncryption,
            ),
        );
    }

    public function getSignature(X509Credential $credential, string $algorithmSignature): SignatureWriter
    {
        return new SignatureWriter(
            $credential->getCertificate(),
            $credential->getPrivateKey(),
            $algorithmSignature,
        );
    }

    protected function makeCertificate(?string $data): X509Certificate
    {
        $cert = new X509Certificate();

        if ($data === null) {
            return $cert;
        }

        if (file_exists($data) && is_readable($data)) {
            $data = file_get_contents($data);

            if (false === $data) {
                throw new \RuntimeException('Unable to read file');
            }
        }

        if (str_starts_with($data, '-----BEGIN CERTIFICATE-----')) {
            return $cert->loadPem($data);
        }

        return $cert->setData($data);
    }
}
