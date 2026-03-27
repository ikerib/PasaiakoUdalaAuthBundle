# PasaiakoUdalaAuthBundle

Dual authentication bundle for Symfony: **LDAP** (Active Directory) + **Digital Certificate** (Izenpe OAuth2 / Giltza).

## Features

- **LDAP authentication**: Active Directory compatible with recursive group search
- **Certificate authentication**: Izenpe integration via OAuth2 (Giltza)
- **Visual selector**: page to choose authentication method (LDAP or Certificate)
- **Role mapping**: maps LDAP groups to Symfony roles
- **Automatic integration**: when a user authenticates by certificate, the bundle looks up their LDAP account by DNI
- **Event system**: customize roles and post-authentication actions
- **Cache**: optional cache for LDAP groups and attributes
- **Built-in controller**: ready-to-use routes without creating your own controllers
- **Fully configurable**: via YAML

## Requirements

- PHP >= 8.1
- `ext-ldap` extension
- Symfony 6.4, 7.x or 8.x
- [KnpUOAuth2ClientBundle](https://github.com/knpuniversity/oauth2-client-bundle) >= 2.19
- [ikerib/giltza-oauth2](https://github.com/ikerib/giltza-oauth2) >= 1.0

## Documentation

- [Full installation guide](docs/INSTALLATION.md) -- step by step, from scratch
- [Configuration reference](docs/CONFIGURATION.md) -- all available options
- [Events](docs/EVENTS.md) -- customize roles and post-login actions
- [Troubleshooting](docs/TROUBLESHOOTING.md) -- common problems and solutions

## Quick install

```bash
composer require pasaia-udala/auth-bundle
```

For the full step-by-step guide, see [docs/INSTALLATION.md](docs/INSTALLATION.md).

## License

MIT

## Author

Developed by **Pasaia Udala** (IT Department)
