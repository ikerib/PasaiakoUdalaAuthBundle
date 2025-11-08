
# Izenpe OAuth2 konfigurazioa

Izenpe-ren ziurtagiri digitalen bidezko autentifikazioa OAuth2 (Giltza) erabiliz nola konfiguratu azaltzen duen gida.

## Aurreko baldintzak

1. Izenpe-rekin kontratua Giltza OAuth2 erabiltzeko
2. Izenpe-n erregistratutako OAuth2 bezeroa
3. Kredentzialak: `CLIENT_ID` eta `CLIENT_SECRET`
4. Izenpe-ren OAuth2 endpoint URIak
5. Giltza paketea: `ikerib/giltza-oauth2`

## Dependentziak instalatzea

```bash
composer require knpuniversity/oauth2-client-bundle
composer require ikerib/giltza-oauth2
```

## OAuth2 bezeroaren konfigurazioa

```yaml
# config/packages/knpu_oauth2_client.yaml
knpu_oauth2_client:
    clients:
        giltza:
            type: 'generic'
            provider_class: Ikerib\OAuth2\Client\Provider\Giltza
            
            # Zure aplikazioaren kredentzialak
            client_id: '%env(OAUTH_GILTZA_CLIENT_ID)%'
            client_secret: '%env(OAUTH_GILTZA_CLIENT_SECRET)%'
            
            # Callback ruta (security.yaml-n PUBLIC_ACCESS izan behar du)
            redirect_route: app_oauth_check
            redirect_params: {}
            
            # Izenpe endpointak
            provider_options:
                urlAuthorize: '%env(OAUTH_GILTZA_URL_AUTHORIZE)%'
                urlAccessToken: '%env(OAUTH_GILTZA_URL_ACCESS_TOKEN)%'
                urlResourceOwnerDetails: '%env(OAUTH_GILTZA_URL_RESOURCE_OWNER_DETAILS)%'
```

## Inguruneko aldagaien konfigurazioa

```bash
# .env
OAUTH_GILTZA_CLIENT_ID=izenpe_username
OAUTH_GILTZA_CLIENT_SECRET=izenpe_password

# Izenpe endpointak (egiaztatu Izenpe-rekin URLa egokia den)
OAUTH_GILTZA_URL_AUTHORIZE=https://api.izenpe.eus/authorize
OAUTH_GILTZA_URL_ACCESS_TOKEN=https://api.izenpe.eus/token
OAUTH_GILTZA_URL_RESOURCE_OWNER_DETAILS=https://api.izenpe.eus/userinfo
```

Gogoratu: endpoint-ak aldatu egin daitezke; kontsultatu Izenpe/Giltza dokumentazioa.

## Security konfigurazioa

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            custom_authenticators:
                - PasaiaUdala\AuthBundle\Security\CertificateAuthenticator
            
            # OAuth2 callback-rako sarbidea publiko izan behar du
            pattern: ^/
            lazy: true

    access_control:
        - { path: ^/oauth, roles: PUBLIC_ACCESS }
        - { path: ^/auth-selector, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: ROLE_USER }
```

## Beharrezko ibilbideak (routes)

```yaml
# config/routes.yaml

# OAuth2 fluxua hasteko ruta
app_oauth_connect:
    path: /oauth/connect
    controller: App\Controller\OAuth2Controller::connect

# Callback ruta (Izenpe hemen birbidaltzen du autentikazioaren ondoren)
app_oauth_check:
    path: /oauth/check
    controller: App\Controller\OAuth2Controller::check
```

## OAuth2 kontrolatzailea

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
        // Izenpe-ra birbidaltzen du autentifikaziorako
        return $clientRegistry
            ->getClient('giltza')
            ->redirect(
                ['openid', 'profile'], // Beharrezko scopes
                []
            );
    }

    #[Route('/oauth/check', name: 'app_oauth_check')]
    public function check(Request $request): never
    {
        // CertificateAuthenticator automatikoki ikuskatuko du ruta hau
        // Kode hau ez da inoiz exekutatuko
        throw new \LogicException('This should never be reached!');
    }
}
```

## Autentifikazio fluxua

### 1. Erabiltzaileak "Ziurtagiria" sakatzen du

```html
<!-- Template: @PasaiakoUdalaAuth/security/auth_selector.html.twig -->
<a href="{{ path('app_oauth_connect') }}" class="btn btn-primary">
    🔐 Ziurtagiriarekin sartzea
</a>
```

### 2. Izenpe-ra birbidaltzea

- Erabiltzailea `https://api.izenpe.eus/authorize` helbidera bidaliko da
- Izenpe-k ziurtagiriaren aukeraketa eskatuko du
- Erabiltzaileak bere ziurtagiri digitala hautatuko du

### 3. Callback aplikaziora

- Izenpe-k `/oauth/check?code=...` atzera bidaliko du
- `CertificateAuthenticator` eskaera ikuskatuko du
- Access token-a eskuratzen da
- Ziurtagiriko informazioa (NAN, izena, eta abar) lortzen da

### 4. LDAP integrazioa

- Ziurtagiriko NAN-a ateratzen da
- LDAP-en `employeeID` bidez erabiltzailea bilatzen da
- Aurkitzen badu: rolen eta LDAP taldearen informazioa asignatzen da
- Aurkitzen ez badu: `ROLE_USER` bakarrik ematen zaio

### 5. Erabiltzaile autentifikatua

- Symfony saioa sortzen da
- Erabiltzailea hasierako orrira birbidaliko da

## Ziurtagiriko datuen ateraera

`CertificateAuthenticator`-ek automatikoki ateratzen du:

```php
// LdapUser-en erabilgarri dagoen informazioa
$user->getCertificateData() => [
    'dni' => '12345678X',           // Ziurtagiritik atera da
    'nombre' => 'Jon',
    'apellidos' => 'Doe Surname',
    'email' => 'jdoe@example.com',  // Baldin badago ziurtagiriaren barruan
    // Ziurtagiriko beste datuak
]

$user->isCertificateAuthenticated() => true
```

## Callback Izenpe-n konfiguratu

Izenpe-n zure aplikazioa erregistratzerakoan, **callback URL** zehaztu behar da:

```
https://tu-aplicacion.pasaia.net/oauth/check
```

Gogoratu:
- Production ingurunean HTTPS izan behar du
- `redirect_route: app_oauth_check` parametroarekin bat etorri behar du
- Izenpe-k URL erregistratu gabeko callback-ak ukatuko ditu

## Scopes pertsonalizatuak

```php
// OAuth2Controller::connect()-en
return $clientRegistry
    ->getClient('giltza')
    ->redirect(
        ['openid', 'profile', 'email'], // Scopes gehiagorekin
        []
    );
```

Eskuragarri dauden scopes (Izenpe-rekin kontsultatu):
- `openid`: erabiltzaile identifikatzaile bakarra
- `profile`: izena, abizenak
- `email`: posta elektronikoa

## Arazoak (Troubleshooting)

### Error: "Invalid redirect_uri"

Arrazoia: callback URL-ak ez datoz bat Izenpe-n erregistratutakoarekin

Konponbidea:
1. Egiaztatu Izenpe konfigurazioan dagoen URL-a
2. Berak zehazten duena izan behar du zehazki: `https://tu-dominio.com/oauth/check`
3. Eta ez izan parametro gehigarririk

### Error: "Invalid client credentials"

Arrazoia: `CLIENT_ID` edo `CLIENT_SECRET` okerrak dira

Konponbidea:
1. Egiaztatu kredentzialak `.env` fitxategian
2. Jarri harremanetan Izenpe-rekin kredentzialak egiaztatzeko

### Ez da Izenpe-ra birbidaltzen

Arrazoia: `knpu_oauth2_client` konfigurazioa okerra da

Konponbidea:
1. Egiaztatu `provider_class: Ikerib\OAuth2\Client\Provider\Giltza`
2. Egiaztatu endpoint-URIak `.env`-ean
3. Garapeneko cache garbitu: `php bin/console cache:clear`

### Callback jaso baina autentifikaziorik gabe

Arrazoia: `CertificateAuthenticator` ez dago registroan `security.yaml`-ean

Konponbidea:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            custom_authenticators:
                - PasaiaUdala\AuthBundle\Security\CertificateAuthenticator
                # ^--- Hemen erregistratua egon behar du
```

### DNI-a LDAP-en aurkitu ez da

Sintoma: Erabiltzaileak ziurtagiriarekin autentikatu da baina rolen gabe

Arrazoia: `employeeID` eremua ez dago beteta edo ez dator bat

Konponbidea:
1. Egiaztatu erabiltzailea LDAP-en existitzen den
2. Begiratu `employeeID` eremua:

```bash
ldapsearch -x -H ldap://172.28.64.20:389 \
  -D "cn=ServiceAccount,..." -w "password" \
  -b "dc=pasaia,dc=net" \
  "(employeeID=12345678X)"
```

3. Ez ez badago: bete `employeeID` eremua NANarekin
4. Erabiltzaileak autentikatu egingo du baina soilik `ROLE_USER` izango du

## Debug logs

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['oauth', 'security']
    handlers:
        oauth:
            type: stream
            path: "%kernel.logs_dir%/oauth.log"
            level: debug
            channels: ['oauth', 'security']
```

Log-ek honakoak jasoko dituzte:
- Izenpe-ra birbidaltzeak
- Callback-en jasotzeak
- Ziurtagiriko datuen ateraera
- LDAP-en NAN bidezko bilaketak
- Rol mapaketak

## Probako ziurtagiriak

Garapenerako, Izenpe-k probako ziurtagiriak eskaintzen ditu. Kontsultatu:
- Izenpe dokumentazioa testing inguruneari buruz
- Probako ziurtagiri digitalak
- OAuth2 sandbox-a

## Segurtasuna

### HTTPS derrigorrezkoa

```yaml
# config/packages/security.yaml (produkzioan)
security:
    firewalls:
        main:
            # HTTPS exijitu edo bestelako neurriak
            access_denied_url: /
            require_previous_session: false
```

### OAuth2 state balidazioa

Bundle-a `state` parametroa erabiltzen du CSRF prebenitzeko:
- KnpU OAuth2 Client-ek automatikoki sortzen du
- Callback-ean automatikoki balidatzen da
- Gehigarririk ez dago konfiguratzeko

### Tokenen biltegiratze segurua

OAuth2 token-ak autentifikazioan soilik erabiltzen dira:
- Ez dira sesioan gordetzen
- Ez dira datu-basean gordetzen
- Erabilitakoan bertan botatzen dira

## LDAP integrazioa

Erabiltzaileak ziurtagiriarekin autentikatzen denean:

1. Ziurtagiriko NAN-a ateratzen da
2. LDAP-en `employeeID` eremuan bilatzen da
3. LDAP-en badago:
   - Bere taldeak jasoko dira
   - Symfony roletara mapatuko dira
   - `LdapUser` sortuko da datu guztiekin
4. LDAP-en ez badago:
   - `LdapUser` sortuko da ziurtagiriko datuekin bakarrik
   - `ROLE_USER` bakarrik izango du (defektuz)
   - `isCertificateAuthenticated()` true itzuliko du

Honek aukera ematen du:
- LDAP-en ez dauden kanpoko erabiltzaileak ziurtagiriarekin autentikatzeko
- Langileen rolen kudeaketa LDAP-en zentralizatuta izateko
- Nork ziurtagiriekin autentikatu den auditatzea

## Adibide osoa

Ikusi adibide proiektu osoa:
```
/docs/examples/oauth2-complete-setup/
```

## Baliabideak

- [Izenpe Giltza dokumentazioa](https://www.izenpe.eus/...)
- [KnpU OAuth2 Client Bundle](https://github.com/knpuniversity/oauth2-client-bundle)
- [Giltza OAuth2 Provider](https://github.com/ikerib/giltza-oauth2)
