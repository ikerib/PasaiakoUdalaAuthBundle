# Changelog

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioned according to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.1] - 2026-03-27

### Fixed
- Twig globals (`pasaiako_udala_auth_base_template`, `pasaiako_udala_auth_routes`) are now registered even when LDAP is not configured, fixing a crash on fresh installs

## [1.2.0] - 2026-03-27

### Added
- Built-in `AuthController` with predefined routes (selector, LDAP login, OAuth2)
- `routes.yaml` file to import bundle routes directly
- Event system: `LdapGroupsLoadedEvent` and `PostAuthenticationEvent`
- Configurable route names via `pasaiako_udala_auth.routes`
- Configurable DNI field via `dni_field` (previously hardcoded to `employeeID`)
- Configurable LDAP user attributes via `user_attributes`
- Optional cache for LDAP groups and attributes (`cache_ttl`)
- Configurable base template via `base_template`
- `#[SensitiveParameter]` attribute on password parameters

### Fixed
- LDAP connection now properly resets after `authenticate()` (disconnect in finally block)
- Twig globals injection moved to `prepend()` for improved robustness

### Changed
- `LdapClient` log messages unified in English
- Removed legacy `loadUserByUsername()` method
- LDAP connection type updated to `\LDAP\Connection|null` (PHP 8.1+)
- Documentation fully rewritten in English

## [1.1.0] - 2024-01-XX

### Added
- Certificate authentication via Izenpe OAuth2 (Giltza)
- Automatic LDAP user lookup by DNI
- Visual authentication method selector
- Certificate data storage in `LdapUser`

### Fixed
- `LOGIN_ROUTE` constant updated in `LdapAuthenticator`

## [1.0.0] - 2024-01-XX

### Added
- Initial release
- LDAP authentication with Active Directory support
- Recursive group search with service account
- LDAP group to Symfony role mapping
- YAML configuration
- `LdapUser`, `LdapUserProvider`, `LdapAuthenticator`, `LdapClient`
