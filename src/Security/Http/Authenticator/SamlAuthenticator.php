<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\Authenticator;

use Exception;
use LightSaml\Error\LightSamlValidationException;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Protocol\Response as SamlResponse;
use LightSaml\SamlConstants;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
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
use Throwable;
use Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge\SamlAttributesBadge;
use Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge\SamlProviderBadge;
use Umanit\SamlBundle\Security\Http\Authenticator\Token\SamlToken;
use Umanit\SamlBundle\Security\User\SamlScopedUserProviderInterface;
use Umanit\SamlBundle\Security\User\SamlUserInterface;
use Umanit\SamlBundle\Security\User\SamlUserProviderInterface;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\ResponseServiceInterface;

#[AutoconfigureTag('monolog.logger', ['channel' => 'security'])]
final readonly class SamlAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    /**
     * @param ?UserProviderInterface<UserInterface> $userProvider
     * @param array<string, mixed>                  $options
     */
    public function __construct(
        private HttpUtils $httpUtils,
        private ?UserProviderInterface $userProvider,
        private AuthenticationSuccessHandlerInterface $successHandler,
        private AuthenticationFailureHandlerInterface $failureHandler,
        private array $options,
        private ConfigurationServiceInterface $configurationService,
        private ResponseServiceInterface $responseService,
        private ?LoggerInterface $logger,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $uri = $this->httpUtils->generateUri($request, (string) $this->options['login_path']);

        return new RedirectResponse($uri);
    }

    public function supports(Request $request): bool
    {
        if (!$request->isMethod('GET') && !$request->isMethod('POST')) {
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

        $this->logger->info('SAML Authentication start', ['provider' => $provider]);

        try {
            $this->logger->info('SAML Authentication getting configuration', ['provider' => $provider]);
            $configuration = $this->configurationService->getByProvider($provider);

            $this->logger->info('SAML Authentication getting SAML message', ['provider' => $provider]);
            $samlResponse = $this->responseService->getResponseSamlMessage($request);

            if (null === $samlResponse) {
                $this->logger->error('SAML Authentication SAML message not found', ['provider' => $provider]);

                throw new AuthenticationException('No SAML message found');
            }

            $isStrict = $configuration['strict'] ?? true;
            $this->logger->info('SAML Authentication message validation', [
                'provider' => $provider,
                'strict'   => $isStrict ? 'true' : 'false',
            ]);
            $this->responseService->validate($provider, $samlResponse, $isStrict);
        } catch (Exception $e) {
            $this->logger->error('SAML Authentication message validation error', [
                'exception'      => $e,
                'provider'       => $provider,
                'method'         => $request->getMethod(),
                'uri'            => $request->getRequestUri(),
                'query_params'   => $request->query->all(),
                'request_params' => $request->request->all(),
            ]);

            throw new AuthenticationException($e->getMessage());
        }

        return $this->createPassport($provider, $samlResponse);
    }

    public function createToken(Passport $passport, string $firewallName): SamlToken
    {
        if (!$passport->hasBadge(SamlAttributesBadge::class)) {
            throw new LogicException(\sprintf('Passport should contains a "%s" badge.', SamlAttributesBadge::class));
        }

        $badge = $passport->getBadge(SamlAttributesBadge::class);
        $attributes = [];

        if ($badge instanceof SamlAttributesBadge) {
            $attributes = $badge->getAttributes();
        }

        $badge = $passport->getBadge(SamlProviderBadge::class);
        $providerKey = 'saml';

        if ($badge instanceof SamlProviderBadge) {
            $providerKey = $badge->getProviderKey();
        }

        $this->logger->info('SAML Authentication create token', [
            'provider'   => $providerKey,
            'identifier' => $passport->getUser()->getUserIdentifier(),
        ]);

        return new SamlToken(
            $passport->getUser(),
            $firewallName,
            $passport->getUser()->getRoles(),
            $attributes,
            $providerKey,
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    private function createPassport(string $providerKey, SamlResponse $response): Passport
    {
        $assertion = $response->getFirstAssertion();

        if (null === $assertion) {
            throw new AuthenticationException('No assertion found');
        }

        /** @var Subject|null $subject */
        $subject = $assertion->getSubject();

        if (null === $subject) {
            throw new LightSamlValidationException('No subject found in response');
        }

        /** @var string|null $nameIdValue */
        $nameIdValue = $subject->getNameID()->getValue();

        if (null === $nameIdValue) {
            throw new LightSamlValidationException('No NameID value found in response');
        }

        $nameIdIsEmail = SamlConstants::NAME_ID_FORMAT_EMAIL === $subject->getNameID()->getFormat();

        $this->logger->info('SAML Authentication getting attributes', ['provider' => $providerKey]);
        $attributesItems = $assertion->getFirstAttributeStatement()?->getAllAttributes() ?? [];

        $attributes = [
            '_provider' => $providerKey,
        ];

        foreach ($attributesItems as $attribute) {
            $attributes[$attribute->getName()] = $attribute->getAllAttributeValues();
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $nameIdValue,
                function (string $identifier) use ($nameIdIsEmail, $attributes, $providerKey): UserInterface {
                    try {
                        if ($this->userProvider instanceof SamlScopedUserProviderInterface) {
                            $this->logger->info('SAML Authentication loading user by identifier and provider', [
                                'identifier'    => $identifier,
                                'provider'      => $providerKey,
                                'nameIdIsEmail' => $nameIdIsEmail,
                            ]);
                            $user = $this->userProvider->loadUserByIdentifierAndProvider(
                                $identifier,
                                $providerKey,
                                $attributes,
                            );
                        } elseif ($this->userProvider instanceof SamlUserProviderInterface) {
                            $this->logger->info('SAML Authentication loading user by identifier', [
                                'provider'   => $providerKey,
                                'identifier' => $identifier,
                            ]);

                            $user = $this->userProvider->loadSamlUser($identifier, $providerKey, $attributes);
                        } else {
                            $this->logger->info('SAML Authentication loading user by identifier', [
                                'provider'   => $providerKey,
                                'identifier' => $identifier,
                            ]);

                            $user = $this->userProvider->loadUserByIdentifier($identifier);
                        }

                        if ($user instanceof SamlUserInterface) {
                            $this->logger->info('SAML Authentication set attributes', [
                                'provider'   => $providerKey,
                                'identifier' => $identifier,
                            ]);
                            $user->setSamlAttributes($attributes);
                        }
                    } catch (Throwable $exception) {
                        $this->logger->error('SAML Authentication an error occurred while loading the user', [
                            'exception'  => $exception,
                            'identifier' => $identifier,
                        ]);

                        if ($exception instanceof UserNotFoundException) {
                            throw $exception;
                        }

                        throw new AuthenticationException('The authentication failed.', 0, $exception);
                    }

                    return $user;
                },
            ),
            [
                new SamlAttributesBadge(
                    $attributes,
                    $this->options['saml_restrictions'] ?? [],
                ),
                new SamlProviderBadge($providerKey),
            ],
        );
    }
}
