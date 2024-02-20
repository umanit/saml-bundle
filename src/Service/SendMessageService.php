<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\XmlDSig\SignatureWriter;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Symfony\Component\HttpFoundation\Response;

class SendMessageService implements SendMessageServiceInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected ?MessageContext $messageContext = null
    ) {
        $this->messageContext = new MessageContext();
    }

    public function send(string $provider, SamlMessage $message, string $bindingType): Response
    {
        $config = $this->configurationService->getByProvider($provider);

        if (null !== ($credential = $this->getX509Credentials($config['sp']))) {
            $message->setSignature($this->signature($credential));
        }

        $messageContext = new MessageContext();
        $messageContext->setMessage($message);

        if ($this->messageContext->getMessage() instanceof SamlMessage) {
            $messageContext->getMessage()->setRelayState(
                $this->messageContext->getMessage()->getRelayState()
            );
        }

        $binding = (new BindingFactory())->create($bindingType);

        return $binding->send($messageContext);
    }

    protected function signature(X509Credential $credential): SignatureWriter
    {
        return new SignatureWriter(
            $credential->getCertificate(),
            $credential->getPrivateKey(),
            XMLSecurityDSig::SHA256
        );
    }

    protected function getX509Credentials(array $spConfig): ?X509Credential
    {
        $x509Cert = $spConfig['x509cert'] ?? null;
        $privateKey = $spConfig['private_key'] ?? null;
        $privateKeyPassphrase = $spConfig['private_key_passphrase'] ?? '';

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
                XMLSecurityKey::RSA_SHA256
            )
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
        }

        if (str_starts_with($data, '-----BEGIN CERTIFICATE-----')) {
            return $cert->loadPem($data);
        }

        return $cert->setData($data);
    }
}
