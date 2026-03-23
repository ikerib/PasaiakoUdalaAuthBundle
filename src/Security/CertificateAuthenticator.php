<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Security;

use PasaiaUdala\AuthBundle\Event\LdapGroupsLoadedEvent;
use PasaiaUdala\AuthBundle\Event\PostAuthenticationEvent;
use PasaiaUdala\AuthBundle\Service\LdapClient;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * CertificateAuthenticator - Authenticates users via Izenpe certificate (OAuth2)
 */
class CertificateAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RouterInterface $router,
        private readonly LdapClient $ldapClient,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $homeRoute = 'app_home',
        private readonly string $loginSelectorRoute = 'app_login',
        private readonly string $oauthCheckRoute = 'oauth_check',
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Solo soporta la ruta de callback de OAuth
        return $request->attributes->get('_route') === $this->oauthCheckRoute;
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('giltza');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var \Giltza\OAuth2\Client\Provider\GiltzaUser $giltzaUser */
                $giltzaUser = $client->fetchUserFromToken($accessToken);

                $userDataArray = $giltzaUser->toArray();

                // Extraer DNI del usuario
                $dni = $userDataArray['dni'] ?? null;

                if (!$dni) {
                    throw new AuthenticationException('No se pudo obtener el DNI del certificado');
                }

                // Buscar usuario en LDAP por DNI (campo 'sn')
                $ldapUser = $this->ldapClient->findUserByDni($dni);

                if ($ldapUser !== null) {
                    $username = $ldapUser['username'];
                    $groups = $ldapUser['groups'];
                    $roles = $this->ldapClient->mapGroupsToRoles($groups);

                    $event = new LdapGroupsLoadedEvent($username, $groups, $roles, 'certificate');
                    $this->eventDispatcher->dispatch($event);
                    $roles = $event->getRoles();

                    return new LdapUser($username, $roles, $groups, $userDataArray);
                } else {
                    // Usuario NO encontrado en LDAP - usar DNI como username con rol básico
                    // Esto permite acceso con certificado válido aunque no esté en LDAP
                    return new LdapUser(
                        $dni,              // username = DNI
                        ['ROLE_USER'],     // solo rol básico
                        [],                // sin grupos
                        $userDataArray     // Datos del certificado
                    );
                }
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof LdapUser) {
            $this->eventDispatcher->dispatch(new PostAuthenticationEvent($user, 'certificate'));
        }

        return new RedirectResponse($this->router->generate($this->homeRoute));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set('_security.last_error', $exception);

        return new RedirectResponse($this->router->generate($this->loginSelectorRoute));
    }

    /**
     * Entry point: redirige al selector de método de autenticación
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate($this->loginSelectorRoute));
    }
}
