<?php
declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\SamlConstants;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Core\User\UserInterface;
use Umanit\SamlBundle\Validator\SloValidatorInterface;

class SloService implements SloServiceInterface
{
    public function __construct(
        protected SamlMessageServiceInterface $samlMessageService,
        protected SloValidatorInterface $sloValidator,
        protected Security $security,
        protected MetadataServiceInterface $metadataService,
        protected X509CertificatServiceInterface $x509CertificatService,
    ) {
    }

    public function logoutDepuislapp(string $provider, UserInterface $user)
    {
        $ownEntityDescriptor = $this->metadataService->getOwnEntityDescriptor($provider);
        $signature = $ownEntityDescriptor->getSignature();
        // SP
        $entityDescriptor = $this->metadataService->getEntityDescriptor($provider);

        $idpSsoDescriptor = $entityDescriptor->getFirstIdpSsoDescriptor();
        $format = $idpSsoDescriptor?->getAllNameIDFormats()[0] ?? SamlConstants::NAME_ID_FORMAT_PERSISTENT;
        $issuer = new Issuer($ownEntityDescriptor->getEntityID());
        $nameId = new NameID($user->getUserIdentifier(), $format);

        $logoutRequest = new LogoutRequest();
        $logoutRequest
            ->setId(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($idpSsoDescriptor->getFirstSingleLogoutService()->getLocation())
            ->setIssuer($issuer)
            ->setSignature($signature)
            ->setNameID($nameId)
        ;

        $bindingFactory = new \LightSaml\Binding\BindingFactory();
        $postBinding = $bindingFactory->create(\LightSaml\SamlConstants::BINDING_SAML2_HTTP_POST);

        $messageContext = new \LightSaml\Context\Profile\MessageContext();
        $messageContext->setMessage($logoutRequest);

        /** @var \Symfony\Component\HttpFoundation\Response $httpResponse */
        $httpResponse = $postBinding->send($messageContext);
        dd($httpResponse);

        return $httpResponse;
    }

    public function logout(Request $request, string $provider): ?Response
    {
        $response = $this->getLogoutResponseSamlMessage($request);

        dd($response);
        if (null === $response) {
            throw new LogoutException("No SAML message found");
        }

        $this->validate($provider, $response);

        return $this->security->logout(false);
    }

    public function getLogoutResponseSamlMessage(Request $request): ?LogoutResponse
    {
        $messageContext = $this->samlMessageService->getSamlMessage($request);

        $response = $messageContext->asLogoutResponse();

        if (!$response instanceof LogoutResponse) {
            return null;
        }

        return $response;
    }

    public function validate(string $provider, LogoutResponse $samlMessage, bool $strict = true): void
    {
        $this->sloValidator->validate($provider, $samlMessage, $strict);
    }
}
