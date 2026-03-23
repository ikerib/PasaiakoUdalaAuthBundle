# PasaiakoUdalaAuthBundle - Lista de tareas

## Bugs / Correcciones

- [x] **T01** - Rutas hardcodeadas: hacer configurables `app_home`, `app_login`, `app_login_ldap`, `oauth_connect`, `oauth_check` desde `Configuration.php`
- [x] **T02** - Conexion LDAP compartida: tras `authenticate()` la conexion queda bindeada como usuario, puede afectar a `getUserGroups()`. Separar conexiones o hacer disconnect tras auth.
- [x] **T03** - Texto hardcodeado en `login.html.twig`: eliminar "Txalaparta SMS Microservice v1.0" del footer o hacerlo configurable via variable Twig

## Arquitectura

- [x] **T04** - Crear `AuthController` opcional con las rutas del bundle (selector, login LDAP, OAuth connect/check)
- [x] **T05** - Anadir `src/Resources/config/routes.yaml` para que la app host importe las rutas del bundle
- [x] **T06** - Dispatchar eventos Symfony: `PostAuthenticationEvent` y `LdapGroupsLoadedEvent` para permitir hooks desde la app host

## Seguridad

- [x] **T07** - Documentar/integrar rate limiting contra fuerza bruta en login LDAP
- [x] **T08** - Marcar parametro password con `#[SensitiveParameter]` (PHP 8.2+) en `authenticate()` y en el CustomCredentials callback

## Funcionalidad

- [x] **T09** - Cache de grupos/atributos LDAP con TTL configurable para evitar consultas LDAP en cada request
- [~] **T10** - ~~Crear tests unitarios~~ (descartado por el usuario)
- [x] **T11** - Atributos LDAP configurables: permitir definir en YAML que campos leer en lugar de tenerlos hardcodeados
- [x] **T12** - Campo de busqueda por DNI configurable: permitir cambiar `employeeID` por otro campo LDAP desde configuracion

## Mejoras menores

- [x] **T13** - Unificar idioma de logs a ingles
- [x] **T14** - Eliminar `loadUserByUsername()` legacy (Symfony < 6 no esta soportado)
- [x] **T15** - Tipar `$connection` como `\LDAP\Connection|null` y eliminar check dead code en `connect()`

---

## 🔴 Bugs / Correcciones (v2)

- [x] **T16** - `getPath()` devuelve `__DIR__` (src/) en vez de la raiz del bundle. `AbstractBundle` espera que devuelva el directorio raiz. Funciona por casualidad porque `Resources/` esta dentro de `src/`. Si se mueve `Resources/` al layout estandar, rompe.
- [x] **T17** - Inyeccion de Twig globals es fragil: en `PasaiakoUdalaAuthExtension::load()` se usa `getDefinition('twig')` que puede fallar si TwigBundle registra `twig` como alias. Mover a `prepend()` o usar un compiler pass.
- [x] **T18** - Validacion de configuracion incompleta: si `host` esta definido pero `base_dn` o `user_dn_pattern` son null, el bundle carga servicios pero va a crashear en runtime. Añadir validacion en la extension.

## 🟡 Mejoras importantes

- [x] **T19** - `LdapUser` no es serializable para sesiones: no implementa `__serialize()` / `__unserialize()`. Con `certificateData` o `ldapAttributes` grandes, puede causar problemas de sesion.
- [ ] **T20** - Crear tests unitarios: `Configuration` tree builder, `Extension` (servicios no se registran sin host), `LdapClient::mapGroupsToRoles()` (logica pura), `LdapUser` serialization, authenticators con LDAP mockeado.
- [ ] **T21** - Auto-carga de rutas del bundle: implementar `loadRoutes()` en la clase del bundle (Symfony 6.1+ `AbstractBundle`) o un `RoutingConfigurator` para que los usuarios no tengan que crear manualmente el fichero de importacion de rutas.
- [x] **T22** - ~~Crear receta Symfony Flex~~ (ya existe en https://github.com/symfony/recipes-contrib/tree/main/pasaia-udala/auth-bundle/1.2)
- [x] **T23** - Templates extienden `base.html.twig` hardcodeado: ambos templates asumen `{% extends 'base.html.twig' %}`. Añadir opcion de config `base_template` para que el usuario pueda sobreescribirlo.

## 🟢 Mejoras menores

- [ ] **T24** - Crear comando `debug:pasaia-auth`: mostrar estado de la conexion LDAP, probar bind, listar grupos descubiertos para un usuario dado. Muy util para depurar.
- [ ] **T25** - Token CSRF hardcodeado: la clave `'authenticate'` esta en `login.html.twig` y en `LdapAuthenticator`. Hacerlo configurable o al menos documentarlo.
- [ ] **T26** - CSS inline en templates: mover los estilos a un fichero `.css` separado en `Resources/public/` y usar la funcion `asset()`, para que los usuarios puedan sobreescribir estilos limpiamente.
- [ ] **T27** - `PostAuthenticationEvent` no incluye el `Request`: los listeners no pueden hacer cosas como audit logging con IP. Añadir el Request al evento.
- [ ] **T28** - Inconsistencia en inyeccion de dependencias: `CertificateAuthenticator` recibe `RouterInterface` pero `LdapAuthenticator` usa `UrlGeneratorInterface`. Unificar a `UrlGeneratorInterface` (interfaz mas estrecha).
- [ ] **T29** - Añadir `phpstan` y `php-cs-fixer` al `require-dev` del bundle para control de calidad de codigo.
- [ ] **T30** - Documentar integracion completa en README.md: security.yaml ejemplo, .env vars, rutas, configuracion de KnpUOAuth2Client con Giltza.

