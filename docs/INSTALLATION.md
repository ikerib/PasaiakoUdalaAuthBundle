# PasaiakoUdalaAuthBundle instalazioa

Gida honek bundlea nola instalatu eta konfiguratu azalduko dizu pausoz pauso.

## 1. Composer bidezko instalazioa

```bash
composer require pasaia-udala/auth-bundle
```

## 2. Bundle-a erregistratzea

Symfony Flex erabiltzen baduzu, bundle-a automatikoki erregistratuko da. Bestela:

```php
// config/bundles.php
return [
    // ...
    PasaiaUdala\AuthBundle\PasaiakoUdalaAuthBundle::class => ['all' => true],
];
```

## 3. Bundle-a konfiguratu

Sortu konfigurazio fitxategia:

```yaml
# config/packages/pasaiako_udala_auth.yaml
pasaiako_udala_auth:
    server:
        host: 'LDAP_SERVER_IP'
        port: 389
        base_dn: 'dc=domain,dc=net'
        service_account_dn: 'cn=ServiceAccount,ou=ServiceAccounts,dc=domain,dc=net'
        service_account_password: '%env(LDAP_SERVICE_PASSWORD)%'
        user_search_filter: '(sAMAccountName={username})'
        group_search_filter: '(member:1.2.840.113556.1.4.1941:={dn})'
        
    role_mapping:
        'CN=Developers,OU=Groups,DC=domain,DC=net': 'ROLE_DEVELOPER'
        'CN=Admins,OU=Groups,DC=domain,DC=net': 'ROLE_ADMIN'
        
    default_role: 'ROLE_USER'
    
    group_search:
        enabled: true
        use_service_account: true
```

## 4. Inguruneko aldagaiak (Environment variables) konfiguratu

```bash
# .env
LDAP_SERVICE_PASSWORD=your_service_account_password
```

## 5. OAuth2 bezeroa konfiguratu (Izenpe)

```yaml
# config/packages/knpu_oauth2_client.yaml
knpu_oauth2_client:
    clients:
        giltza:
            type: 'generic'
            provider_class: Ikerib\OAuth2\Client\Provider\Giltza
            client_id: '%env(OAUTH_GILTZA_CLIENT_ID)%'
            client_secret: '%env(OAUTH_GILTZA_CLIENT_SECRET)%'
            redirect_route: app_oauth_check
            redirect_params: {}
            
            provider_options:
                urlAuthorize: '%env(OAUTH_GILTZA_URL_AUTHORIZE)%'
                urlAccessToken: '%env(OAUTH_GILTZA_URL_ACCESS_TOKEN)%'
                urlResourceOwnerDetails: '%env(OAUTH_GILTZA_URL_RESOURCE_OWNER_DETAILS)%'
```

```bash
# .env
OAUTH_GILTZA_CLIENT_ID=izenpe_username
OAUTH_GILTZA_CLIENT_SECRET=izenpe_password
OAUTH_GILTZA_URL_AUTHORIZE=https://api.izenpe.eus/authorize
OAUTH_GILTZA_URL_ACCESS_TOKEN=https://api.izenpe.eus/token
OAUTH_GILTZA_URL_RESOURCE_OWNER_DETAILS=https://api.izenpe.eus/userinfo
```

## 6. Security konfigurazioa

```yaml
# config/packages/security.yaml
security:
    providers:
        ldap_user_provider:
            id: PasaiaUdala\AuthBundle\Security\LdapUserProvider

    firewalls:
        main:
            lazy: true
            provider: ldap_user_provider
            
            custom_authenticators:
                - PasaiaUdala\AuthBundle\Security\LdapAuthenticator
                - PasaiaUdala\AuthBundle\Security\CertificateAuthenticator
            
            logout:
                path: app_logout
                target: app_auth_selector

    access_control:
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/oauth, roles: PUBLIC_ACCESS }
        - { path: ^/auth-selector, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: ROLE_USER }
```

## 7. Ibilbideak (Routes) sortu

```yaml
# config/routes.yaml
app_auth_selector:
    path: /auth-selector
    controller: App\Controller\SecurityController::authSelector

app_login_ldap:
    path: /login/ldap
    controller: App\Controller\SecurityController::loginLdap

app_logout:
    path: /logout
    methods: GET

app_oauth_connect:
    path: /oauth/connect
    controller: App\Controller\OAuth2Controller::connect

app_oauth_check:
    path: /oauth/check
    controller: App\Controller\OAuth2Controller::check
```

## 8. Kontrolatzaileak (Controllers) sortu

### SecurityController.php

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/auth-selector', name: 'app_auth_selector')]
    public function authSelector(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('@PasaiakoUdalaAuth/security/auth_selector.html.twig');
    }

    #[Route('/login/ldap', name: 'app_login_ldap')]
    public function loginLdap(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@PasaiakoUdalaAuth/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
```

### OAuth2Controller.php

```php
<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class OAuth2Controller extends AbstractController
{
    #[Route('/oauth/connect', name: 'app_oauth_connect')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('giltza')
            ->redirect(['openid', 'profile'], []);
    }

    #[Route('/oauth/check', name: 'app_oauth_check')]
    public function check(Request $request): never
    {
        // CertificateAuthenticator ikuskatuko du ruta hau
        throw new \LogicException('This should never be reached!');
    }
}
```

## 9. Instalazioa egiaztatu

```bash
# Egiaztatu bundle-a erregistratuta dagoela
php bin/console debug:container | grep -i ldap

# Egiaztatu ibilbideak (routes)
php bin/console debug:router | grep -E "(auth|login|oauth)"

# Egiaztatu konfigurazioa
php bin/console debug:config pasaiako_udala_auth
```

## 10. Autentifikazioa probatu

1. Joan `/auth-selector` → Ikusi metodo hautatzailea
2. Aukeratu "LDAP" → Erabiltzaile/Password inprimakia
3. Aukeratu "Ziurtagiria" → Izenpe OAuth2-ra birbidaltzen du
4. Bi metodoek behar bezala autentikatu beharko lukete

## Arazoen konponketa (Troubleshooting)

Ikusi [TROUBLESHOOTING.md](TROUBLESHOOTING.md) arazo ohikoen konpontzeko.

## Hurrengo pausoak

- [LDAP konfigurazio aurreratua](LDAP.md)
- [Izenpe konfigurazio aurreratua](IZENPE.md)
- [Txantiloiak pertsonalizatzea](TEMPLATES.md)
