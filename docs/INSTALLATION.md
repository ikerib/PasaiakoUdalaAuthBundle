# Installation guide

This guide explains step by step how to install and configure PasaiakoUdalaAuthBundle in a Symfony application.

## Table of contents

1. [Install dependencies](#1-install-dependencies)
2. [Register the bundle](#2-register-the-bundle)
3. [Environment variables](#3-environment-variables)
4. [Configure the bundle](#4-configure-the-bundle)
5. [Configure the OAuth2 client (Izenpe)](#5-configure-the-oauth2-client-izenpe)
6. [Configure security.yaml](#6-configure-securityyaml)
7. [Configure routes](#7-configure-routes)
8. [Customize the base template](#8-customize-the-base-template)
9. [Verify the installation](#9-verify-the-installation)
10. [Test authentication](#10-test-authentication)

---

## 1. Install dependencies

Install the bundle and its dependencies:

```bash
composer require pasaia-udala/auth-bundle
composer require knpuniversity/oauth2-client-bundle
composer require ikerib/giltza-oauth2
```

The bundle requires the PHP `ldap` extension. If you don't have it installed:

```bash
# Debian/Ubuntu
sudo apt install php-ldap

# Verify it is active
php -m | grep ldap
```

---

## 2. Register the bundle

If you use **Symfony Flex**, the bundle is registered automatically. Otherwise, add it manually:

```php
// config/bundles.php
return [
    // ... other bundles
    PasaiaUdala\AuthBundle\PasaiaUdalaAuthBundle::class => ['all' => true],
];
```

> **Note about naming**: The PHP namespace of the bundle is `PasaiaUdala\AuthBundle`, but the configuration key is `pasaiako_udala_auth`. Both names are correct and coexist by design.

---

## 3. Environment variables

Add the following variables to your `.env` file:

```bash
# .env

###> pasaia-udala/auth-bundle ###

# --- LDAP ---
LDAP_HOST=192.168.1.10
LDAP_PORT=389
LDAP_ENCRYPTION=none
LDAP_BASE_DN="dc=yourdomain,dc=net"
LDAP_USER_DN_PATTERN="uid={username},ou=users,dc=yourdomain,dc=net"

# Service account for LDAP searches (groups, attributes, DNI lookup)
# Leave empty if the server allows anonymous searches
LDAP_BIND_DN="cn=ServiceAccount,ou=ServiceAccounts,dc=yourdomain,dc=net"
LDAP_BIND_PASSWORD=your_service_account_password

# --- OAuth2 Izenpe (Giltza) ---
OAUTH_GILTZA_CLIENT_ID=your_client_id
OAUTH_GILTZA_CLIENT_SECRET=your_client_secret
OAUTH_GILTZA_URL_AUTHORIZE=https://api.izenpe.eus/authorize
OAUTH_GILTZA_URL_ACCESS_TOKEN=https://api.izenpe.eus/token
OAUTH_GILTZA_URL_RESOURCE_OWNER_DETAILS=https://api.izenpe.eus/userinfo

###< pasaia-udala/auth-bundle ###
```

In production, define these values in `.env.local` (which is not committed to git).

---

## 4. Configure the bundle

Create the bundle configuration file:

```yaml
# config/packages/pasaiako_udala_auth.yaml

pasaiako_udala_auth:

    # --- LDAP Server ---
    server:
        host: '%env(LDAP_HOST)%'
        port: '%env(int:LDAP_PORT)%'
        encryption: '%env(LDAP_ENCRYPTION)%'       # none, ssl or tls
        base_dn: '%env(LDAP_BASE_DN)%'
        user_dn_pattern: '%env(LDAP_USER_DN_PATTERN)%'
        bind_dn: '%env(LDAP_BIND_DN)%'
        bind_password: '%env(LDAP_BIND_PASSWORD)%'

    # --- LDAP group to Symfony role mapping ---
    # Key: LDAP group name (CN). Value: Symfony role.
    # Comparison is case-insensitive.
    role_mapping:
        informatika: ROLE_ADMIN
        GGBB: ROLE_KUDEATU
        langilea: ROLE_USER

    # Default role for authenticated users without a mapped group
    default_role: ROLE_USER

    # --- Group search ---
    group_search:
        enabled: true
        base_dn: null                          # null = uses server.base_dn
        filter: '(member={user_dn})'           # {user_dn} is replaced with the user's DN
        recursive: true                        # search nested groups

    # --- DNI field in LDAP ---
    # LDAP attribute containing the user's DNI.
    # Used in certificate authentication to look up the user in LDAP.
    dni_field: employeeID

    # --- LDAP user attributes ---
    # Attributes fetched from the directory and stored in LdapUser.
    # Accessible via $user->getLdapAttribute('department') or getters like $user->getMail().
    user_attributes:
        - department
        - displayName
        - extensionName
        - mail
        - preferredLanguage
        - description

    # --- Cache ---
    # TTL in seconds for caching LDAP groups and attributes (0 = disabled).
    # Avoids LDAP queries on every request during session refresh.
    cache_ttl: 0

    # --- Base template ---
    # Twig template extended by the bundle views.
    # Must define a {% block body %} and a {% block title %}.
    base_template: base.html.twig

    # --- Route names ---
    # Route names used internally by the bundle.
    # If your application uses different names, override them here.
    routes:
        home: app_home                  # redirect after successful login
        login_selector: app_login       # authentication method selection page
        login_ldap: app_login_ldap      # LDAP login form
        oauth_connect: oauth_connect    # start OAuth2 flow
        oauth_check: oauth_check        # OAuth2 callback
```

---

## 5. Configure the OAuth2 client (Izenpe)

This step is required for digital certificate authentication. Create the file:

```yaml
# config/packages/knpu_oauth2_client.yaml

knpu_oauth2_client:
    clients:
        giltza:
            type: generic
            provider_class: Ikerib\OAuth2\Client\Provider\Giltza
            client_id: '%env(OAUTH_GILTZA_CLIENT_ID)%'
            client_secret: '%env(OAUTH_GILTZA_CLIENT_SECRET)%'
            redirect_route: oauth_check        # must match routes.oauth_check
            redirect_params: {}
            provider_options:
                urlAuthorize: '%env(OAUTH_GILTZA_URL_AUTHORIZE)%'
                urlAccessToken: '%env(OAUTH_GILTZA_URL_ACCESS_TOKEN)%'
                urlResourceOwnerDetails: '%env(OAUTH_GILTZA_URL_RESOURCE_OWNER_DETAILS)%'
```

> **Important**: The `redirect_route` must match the route name configured in `pasaiako_udala_auth.routes.oauth_check`. In Izenpe, the registered callback URL must be the public URL of that route (e.g. `https://yourapp.pasaia.net/connect/giltza/check`).

If you don't need certificate authentication, you can skip this step and not register `CertificateAuthenticator` in `security.yaml`.

---

## 6. Configure security.yaml

```yaml
# config/packages/security.yaml

security:
    # --- User Provider ---
    providers:
        ldap_provider:
            id: PasaiaUdala\AuthBundle\Security\LdapUserProvider

    # --- Firewalls ---
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            lazy: true
            provider: ldap_provider

            # Entry point: defines which authenticator handles the redirect
            # when an unauthenticated user tries to access a protected area.
            entry_point: PasaiaUdala\AuthBundle\Security\LdapAuthenticator

            # Authenticators: both active in parallel
            custom_authenticators:
                - PasaiaUdala\AuthBundle\Security\LdapAuthenticator
                - PasaiaUdala\AuthBundle\Security\CertificateAuthenticator

            logout:
                path: app_logout
                target: app_login         # redirect to selector after logout

            # (Optional) Rate limiting to prevent brute force on LDAP
            # Requires: composer require symfony/rate-limiter
            # login_throttling:
            #     max_attempts: 5
            #     interval: '15 minutes'

    # --- Access control ---
    access_control:
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/connect, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: ROLE_USER }
```

### LDAP only (without certificate)

If you don't need certificate authentication, remove `CertificateAuthenticator` and the `entry_point`:

```yaml
firewalls:
    main:
        lazy: true
        provider: ldap_provider
        custom_authenticators:
            - PasaiaUdala\AuthBundle\Security\LdapAuthenticator
        logout:
            path: app_logout
            target: app_login_ldap
```

---

## 7. Configure routes

You have two options: use the routes included in the bundle or define your own routes with your own controllers.

### Option A: Use the bundle routes (recommended)

The bundle includes an `AuthController` with predefined routes. Import them in your application:

```yaml
# config/routes/pasaiako_udala_auth.yaml

pasaiako_udala_auth:
    resource: '@PasaiaUdalaAuthBundle/Resources/config/routes.yaml'
```

This registers the following routes:

| Route name | Path | Action |
|------------|------|--------|
| `app_login` | `/login` | Authentication method selector |
| `app_login_ldap` | `/login/ldap` | LDAP login form |
| `oauth_connect` | `/connect/giltza` | Starts OAuth2 flow |
| `oauth_check` | `/connect/giltza/check` | OAuth2 callback |

Also add the logout route and your application's home route:

```yaml
# config/routes.yaml

app_logout:
    path: /logout
    methods: GET

app_home:
    path: /
    controller: App\Controller\HomeController::index
```

### Option B: Define your own controllers

If you prefer full control, create your own controllers. In that case, adjust the route names in `pasaiako_udala_auth.routes` to match yours.

**SecurityController.php**:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function selector(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('@PasaiakoUdalaAuth/auth_selector.html.twig');
    }

    #[Route('/login/ldap', name: 'app_login_ldap')]
    public function loginLdap(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('@PasaiakoUdalaAuth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Intercepted by the Symfony firewall
    }
}
```

**OAuth2Controller.php**:

```php
<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class OAuth2Controller extends AbstractController
{
    #[Route('/connect/giltza', name: 'oauth_connect')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('giltza')
            ->redirect(['openid', 'profile'], []);
    }

    #[Route('/connect/giltza/check', name: 'oauth_check')]
    public function check(): void
    {
        // CertificateAuthenticator intercepts this route.
        // This method is never executed.
    }
}
```

Then configure the route names to match:

```yaml
# config/packages/pasaiako_udala_auth.yaml (routes section)
pasaiako_udala_auth:
    routes:
        home: app_home
        login_selector: app_login
        login_ldap: app_login_ldap
        oauth_connect: oauth_connect
        oauth_check: oauth_check
```

---

## 8. Customize the base template

The bundle views (`auth_selector.html.twig` and `login.html.twig`) extend the template configured in `base_template`. The default is `base.html.twig`.

Your base template must define at least the `title` and `body` blocks:

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}My Application{% endblock %}</title>
    {% block stylesheets %}{% endblock %}
</head>
<body>
    {% block body %}{% endblock %}
    {% block javascripts %}{% endblock %}
</body>
</html>
```

If your base template has a different name, configure it:

```yaml
pasaiako_udala_auth:
    base_template: 'layouts/app.html.twig'
```

### Overriding bundle views

If you want to customize the views, create the files in your project. Symfony will use them instead of the bundle's:

```
templates/bundles/PasaiaUdalaAuthBundle/auth_selector.html.twig
templates/bundles/PasaiaUdalaAuthBundle/login.html.twig
```

Or render your own templates in your controllers (Option B).

---

## 9. Verify the installation

Run these commands to check that everything is properly registered:

```bash
# Verify the bundle is registered
php bin/console debug:container --tag=controller.service_arguments | grep PasaiaUdala

# Verify routes
php bin/console debug:router | grep -E "(login|oauth|connect)"

# Verify bundle configuration
php bin/console debug:config pasaiako_udala_auth

# Verify registered authenticators
php bin/console debug:firewall main

# Clear cache (important after configuration changes)
php bin/console cache:clear
```

---

## 10. Test authentication

1. Navigate to `/login` -- you should see the authentication method selector
2. Click **LDAP** -- you should see the login form
3. Enter a username and password from your LDAP directory
4. After successful login, you should be redirected to the `app_home` route
5. Check the user's roles with the Symfony Profiler (debug toolbar > Security)

To test certificate authentication:
1. From `/login`, click **Ziurtagiria** (Certificate)
2. You will be redirected to Izenpe to select your digital certificate
3. After authenticating, Izenpe returns you to the application
4. The bundle looks up your DNI in LDAP and assigns the corresponding roles

---

## Bundle structure

For reference, these are the main components of the bundle:

```
src/
  PasaiaUdalaAuthBundle.php              # Bundle class
  DependencyInjection/
    Configuration.php                     # Configuration tree (pasaiako_udala_auth)
    PasaiaUdalaAuthExtension.php          # DI Extension (loads services and parameters)
  Controller/
    AuthController.php                    # Built-in controller (selector, login, oauth)
  Security/
    LdapAuthenticator.php                 # LDAP authenticator (username/password form)
    CertificateAuthenticator.php          # OAuth2 authenticator (Izenpe certificate)
    LdapUserProvider.php                  # User provider (loads users from LDAP)
    LdapUser.php                          # User class (roles, groups, LDAP attributes)
  Service/
    LdapClient.php                        # LDAP client (connection, auth, searches, cache)
  Event/
    LdapGroupsLoadedEvent.php             # Event: groups loaded (modify roles)
    PostAuthenticationEvent.php           # Event: post-login (audit, sync, etc.)
  Resources/
    config/
      services.yaml                       # Service definitions
      routes.yaml                         # Routes included in the bundle
    views/
      auth_selector.html.twig             # View: method selector
      login.html.twig                     # View: LDAP login form
```
