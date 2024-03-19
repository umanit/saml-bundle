<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\Authenticator;

use LightSaml\Error\LightSamlValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge\SamlAttributesBadge;
use Umanit\SamlBundle\Security\Http\Authenticator\Token\SamlToken;
use Umanit\SamlBundle\Security\User\SamlUserInterface;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\ResponseServiceInterface;
use LightSaml\Model\Protocol\Response as SamlResponse;

class SamlAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly HttpUtils $httpUtils,
        private readonly ?UserProviderInterface $userProvider,
        private readonly AuthenticationSuccessHandlerInterface $successHandler,
        private readonly AuthenticationFailureHandlerInterface $failureHandler,
        private readonly array $options,
        private readonly ConfigurationServiceInterface $configurationService,
        private readonly ResponseServiceInterface $responseService
    ) {
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $uri = $this->httpUtils->generateUri($request, (string) $this->options['login_path']);

        return new RedirectResponse($uri);
    }

    public function supports(Request $request): ?bool
    {
        if (!$request->isMethod('POST')) {
            return false;
        }

        return 'umanit_saml_acs' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        if (!$request->hasSession()) {
            throw new SessionUnavailableException('Session is not available');
        }

        $provider = $request->attributes->get('provider');

        try {
            $configuration = $this->configurationService->getByProvider($provider);
        } catch (\Exception $e) {
            throw new AuthenticationException($e->getMessage());
        }

        try {
            $samlResponse = $this->responseService->getSamlMessage($request);

            if (null === $samlResponse) {
                throw new AuthenticationException('No SAML message found');
            }

            $this->responseService->validateSamlMessage($provider, $samlResponse);
        } catch (\Exception $e) {
            throw new AuthenticationException($e->getMessage());
        }

        return $this->createPassport($samlResponse);
    }

    private function createPassport(SamlResponse $response): Passport
    {
        $assertion = $response->getFirstAssertion();

        if (null === $assertion) {
            throw new AuthenticationException('No assertion found');
        }

        $nameIdValue = $assertion->getSubject()?->getNameID()?->getValue();

        if (null === $nameIdValue) {
            throw new LightSamlValidationException('No NameID value found in response');
        }

        $attributesItems = $assertion->getFirstAttributeStatement()?->getAllAttributes() ?? [];

        $attributes = [];

        foreach ($attributesItems as $attribute) {
            $attributes[$attribute->getName()] = $attribute->getAllAttributeValues();
        }

        return new SelfValidatingPassport(
            new UserBadge($nameIdValue, function (string $identifier) use ($attributes): UserInterface {
                try {
                    $user = $this->userProvider->loadUserByIdentifier($identifier);

                    if ($user instanceof SamlUserInterface) {
                        $user->setSamlAttributes($attributes);
                    }
                } catch (\Throwable $exception) {
                    if ($exception instanceof UserNotFoundException) {
                        throw $exception;
                    }

                    throw new AuthenticationException('The authentication failed.', 0, $exception);
                }

                return $user;
            }),
            [
                new SamlAttributesBadge($attributes)
            ]
        );
    }

    public function createToken(Passport $passport, string $firewallName): SamlToken
    {
        if (!$passport->hasBadge(SamlAttributesBadge::class)) {
            throw new LogicException(sprintf('Passport should contains a "%s" badge.', SamlAttributesBadge::class));
        }

        $badge = $passport->getBadge(SamlAttributesBadge::class);
        $attributes = [];

        if ($badge instanceof SamlAttributesBadge) {
            $attributes = $badge->getAttributes();
        }

        return new SamlToken(
            $passport->getUser(),
            $firewallName,
            $passport->getUser()->getRoles(),
            $attributes
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }
}
