<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Security;

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

/**
 * CertificateAuthenticator - Authenticates users via Izenpe certificate (OAuth2)
 */
class CertificateAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RouterInterface $router,
        private readonly LdapClient $ldapClient
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Solo soporta la ruta de callback de OAuth
        return $request->attributes->get('_route') === 'oauth_check';
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
                    // Usuario encontrado en LDAP - usar su username y grupos
                    $username = $ldapUser['username'];
                    $groups = $ldapUser['groups'];
                    $roles = $this->ldapClient->mapGroupsToRoles($groups);

                    return new LdapUser(
                        $username, // username de LDAP (ej: iibarguren@pasaia.net)
                        $roles,    // roles mapeados de grupos LDAP
                        $groups,   // grupos LDAP
                        $userDataArray // Datos del certificado
                    );
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
        // Redirigir al home después de autenticación exitosa
        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // En caso de error, redirigir al selector de login con mensaje
        $request->getSession()->set('_security.last_error', $exception);

        return new RedirectResponse($this->router->generate('app_login'));
    }

    /**
     * Entry point: redirige al selector de método de autenticación
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
