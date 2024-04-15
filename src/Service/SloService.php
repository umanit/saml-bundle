<?php
declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Error\LightSamlValidationException;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\SamlConstants;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    public function sendLogoutRequest(string $provider, UserInterface $user): Response
    {
        $ownEntityDescriptor = $this->metadataService->getOwnEntityDescriptor($provider);
        $idpSsoDescriptor = $this->metadataService->getEntityDescriptor($provider)->getFirstIdpSsoDescriptor();

        if (null === $idpSsoDescriptor) {
            throw new LightSamlValidationException('No IdP/SP SSO descriptor found in entity descriptor');
        }

        $format = $idpSsoDescriptor->getAllNameIDFormats()[0] ?? SamlConstants::NAME_ID_FORMAT_PERSISTENT;
        $issuer = new Issuer($ownEntityDescriptor->getEntityID());
        $nameId = new NameID($user->getUserIdentifier(), $format);

        $logoutRequest = new LogoutRequest();
        $logoutRequest
            ->setId(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($idpSsoDescriptor->getFirstSingleLogoutService()?->getLocation())
            ->setIssuer($issuer)
            ->setSignature($ownEntityDescriptor->getSignature())
            ->setNameID($nameId)
        ;

        $bindingFactory = new BindingFactory();
        $bindingType = $idpSsoDescriptor->getFirstSingleLogoutService()?->getBinding();
        $postBinding = $bindingFactory->create($bindingType);

        $messageContext = new MessageContext();
        $messageContext->setMessage($logoutRequest);

        $response = $postBinding->send($messageContext);
        if ($bindingType === SamlConstants::BINDING_SAML2_HTTP_REDIRECT && !$response instanceof RedirectResponse) {
            $class = get_class($response);
            throw new HttpException(
                $response->getStatusCode(),
                "Excepted RedirectResponse, $class obtained"
            );
        }

        return $response;
    }

    public function logout(Request $request, string $provider): ?Response
    {
        $response = $this->getLogoutResponseSamlMessage($request);

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
