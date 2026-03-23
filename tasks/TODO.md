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
