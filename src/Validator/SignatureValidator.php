<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Validator;

use LightSaml\Credential\Context\CredentialContextSet;
use LightSaml\Credential\Context\MetadataCredentialContext;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Error\LightSamlSecurityException;
use LightSaml\Error\LightSamlValidationException;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\XmlDSig\AbstractSignatureReader;
use Umanit\SamlBundle\Enums\Mode;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\MetadataServiceInterface;

final readonly class SignatureValidator implements SignatureValidatorInterface
{
    public function __construct(
        protected ConfigurationServiceInterface $configurationService,
        protected MetadataServiceInterface $metadataService,
    ) {
    }

    public function validate(string $provider, SamlMessage $message): void
    {
        $entityDescriptor = $this->metadataService->getEntityDescriptor($provider);

        $providerConfiguration = $this->configurationService->getByProvider($provider);

        if (Mode::SP_INITIATED === $providerConfiguration['type']) {
            $ssoDescriptor = $entityDescriptor->getFirstIdpSsoDescriptor();
        } else {
            $ssoDescriptor = $entityDescriptor->getFirstSpSsoDescriptor();
        }

        if (null === $ssoDescriptor) {
            throw new LightSamlValidationException('No IdP/SP SSO descriptor found in entity descriptor');
        }

        $signatureReader = $message->getSignature();
        if (null === $signatureReader && $message instanceof Response) {
            $signatureReader = $message->getFirstAssertion()?->getSignature();
        }

        if (!$signatureReader instanceof AbstractSignatureReader) {
            throw new LightSamlValidationException('No signature found in response');
        }

        /** @var KeyDescriptor[] $keyDescriptors */
        $keyDescriptors = $ssoDescriptor->getAllKeyDescriptors();

        $credentialCandidates = [];

        foreach ($keyDescriptors as $keyDescriptor) {
            /** @var X509Certificate $certificate */
            $certificate = $keyDescriptor->getCertificate();
            $credentialCandidates[] = new X509Credential($certificate)
                ->setEntityId($entityDescriptor->getEntityID())
                ->addKeyName($certificate->getName())
                ->setUsageType($keyDescriptor->getUse())
                ->setCredentialContext(
                    new CredentialContextSet([
                        new MetadataCredentialContext($keyDescriptor, $ssoDescriptor, $entityDescriptor),
                    ]),
                )
            ;
        }

        // On vérifie que la signature est bien faite avec une des clés publiques de l'IdP
        if (null !== ($x509Thumbprint = $signatureReader->getKey()?->getX509Thumbprint())) {
            $result = [];

            foreach ($credentialCandidates as $credentialCandidate) {
                if ($credentialCandidate->getPublicKey()?->getX509Thumbprint() === $x509Thumbprint) {
                    $result[] = $credentialCandidate;
                }
            }

            $credentialCandidates = $result;
        }

        if (empty($credentialCandidates)) {
            throw new LightSamlValidationException('No valid credential found for signature');
        }

        try {
            $credential = $signatureReader->validateMulti($credentialCandidates);
        } catch (LightSamlSecurityException $e) {
            throw $e;
        }
    }
}
