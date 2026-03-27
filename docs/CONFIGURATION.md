# Configuration reference

All available options in `config/packages/pasaiako_udala_auth.yaml`.

## Full configuration with defaults

```yaml
pasaiako_udala_auth:

    server:
        host: ~                            # (required) LDAP server IP or hostname
        port: 389                          # Port: 389 (LDAP/STARTTLS) or 636 (LDAPS)
        encryption: none                   # none | ssl | tls
        base_dn: ~                         # (required if host) Base DN: dc=domain,dc=net
        user_dn_pattern: ~                 # (required if host) User DN: uid={username},ou=users,...
        bind_dn: ~                         # Service account for searches (optional)
        bind_password: ~                   # Service account password (optional)

    role_mapping: {}                       # Map ldap_group: SYMFONY_ROLE

    default_role: ROLE_USER                # Default role

    group_search:
        enabled: true                      # Enable group search
        base_dn: ~                         # Group search base DN (null = uses server.base_dn)
        filter: '(member={user_dn})'       # LDAP filter ({user_dn} and {username} available)
        recursive: true                    # Recursive search for nested groups

    dni_field: employeeID                  # LDAP attribute containing the DNI
    user_attributes:                       # LDAP attributes to fetch
        - department
        - displayName
        - extensionName
        - mail
        - preferredLanguage
        - description

    cache_ttl: 0                           # Cache TTL in seconds for groups/attributes (0 = no cache)

    base_template: base.html.twig          # Base template extended by bundle views

    routes:
        home: app_home                     # Post-login redirect
        login_selector: app_login          # Method selector
        login_ldap: app_login_ldap         # LDAP form
        oauth_connect: oauth_connect       # OAuth2 start
        oauth_check: oauth_check           # OAuth2 callback
```

---

## LDAP server

### `server.host`

LDAP/Active Directory server IP or hostname. **If not configured, the bundle disables itself** and does not register any services. This allows installing the bundle without breaking the application before configuring it.

```yaml
server:
    host: '192.168.1.10'          # IP address
    host: 'ldap.mydomain.net'     # or hostname
```

### `server.port`

| Port | Usage |
|------|-------|
| `389` | Unencrypted LDAP or STARTTLS |
| `636` | LDAPS (LDAP over SSL) |

In production, use `636` with `encryption: ssl` or `389` with `encryption: tls`.

### `server.encryption`

| Value | Protocol | Generated URI |
|-------|----------|---------------|
| `none` | No encryption | `ldap://host:389` |
| `ssl` | LDAPS | `ldaps://host:636` |
| `tls` | STARTTLS | `ldap://host:389` + STARTTLS |

### `server.base_dn`

Base DN for LDAP searches. Example: `dc=pasaia,dc=net`.

### `server.user_dn_pattern`

Pattern to build the user's DN when authenticating. Use `{username}` as placeholder:

```yaml
# Typical Active Directory
user_dn_pattern: '{username}@yourdomain.net'

# Typical OpenLDAP
user_dn_pattern: 'uid={username},ou=users,dc=yourdomain,dc=net'
```

### `server.bind_dn` and `server.bind_password`

Service account for search operations (groups, attributes, DNI lookup). If your server allows anonymous searches, leave as `null`.

```yaml
bind_dn: 'cn=ServiceAccount,ou=ServiceAccounts,dc=yourdomain,dc=net'
bind_password: '%env(LDAP_BIND_PASSWORD)%'
```

---

## Role mapping

### `role_mapping`

Maps LDAP group names (the CN) to Symfony roles. Comparison is **case-insensitive**.

```yaml
role_mapping:
    informatika: ROLE_ADMIN
    GGBB: ROLE_KUDEATU
    langilea: ROLE_USER
    direccion: ROLE_DIRECCION
```

If a user belongs to the `informatika` group in LDAP, they receive the `ROLE_ADMIN` role in addition to the `default_role`.

### `default_role`

Role assigned to all authenticated users, whether or not they have mapped groups. Default: `ROLE_USER`.

---

## Group search

### `group_search.enabled`

If `false`, no group search is performed and the user only receives the `default_role`.

### `group_search.filter`

LDAP filter for searching a user's groups. Available placeholders:
- `{user_dn}`: full DN of the user
- `{username}`: username

```yaml
# Standard (memberOf)
filter: '(member={user_dn})'

# Active Directory with native recursive search (LDAP_MATCHING_RULE_IN_CHAIN)
filter: '(member:1.2.840.113556.1.4.1941:={user_dn})'

# OpenLDAP with memberUid
filter: '(memberUid={username})'
```

### `group_search.recursive`

If `true`, when a group is found the bundle also searches for that group's parent groups (nested groups). Useful if your directory has a group hierarchy.

---

## DNI field

### `dni_field`

LDAP attribute containing the user's DNI (national identity number). Used in certificate authentication: when Izenpe returns the DNI, the bundle searches LDAP for a user with `(dni_field=DNI)`.

```yaml
dni_field: employeeID          # Default
dni_field: extensionAttribute1 # Another common AD field
dni_field: sn                  # If you use surname as DNI field
```

---

## User attributes

### `user_attributes`

List of LDAP attributes fetched when loading the user. They are stored in `LdapUser` and accessed via:

```php
// Generic getter
$user->getLdapAttribute('department');

// Convenience getters (for the default attributes)
$user->getDepartment();
$user->getDisplayName();
$user->getMail();
$user->getPreferredLanguage();
$user->getExtensionName();
$user->getDescription();
```

You can customize the list to match your LDAP schema:

```yaml
user_attributes:
    - department
    - displayName
    - mail
    - telephoneNumber
    - title
    - manager
```

---

## Cache

### `cache_ttl`

Time in seconds to cache LDAP groups and attributes. Uses Symfony's `cache.app` service.

```yaml
cache_ttl: 0     # No cache (LDAP query on every request)
cache_ttl: 300   # 5 minutes
cache_ttl: 3600  # 1 hour
```

Enabling cache is recommended in production to reduce load on the LDAP server, especially when `refreshUser()` runs on every request.

---

## Base template

### `base_template`

Twig template extended by the bundle views. Must define the `title` and `body` blocks.

```yaml
base_template: base.html.twig             # Default
base_template: 'layouts/app.html.twig'    # If you use a different layout
```

---

## Route names

### `routes`

Route names used internally by the bundle for redirects, template links, and OAuth2 callback detection.

If your application already has routes with different names, override these values to match:

```yaml
routes:
    home: dashboard                    # your main route
    login_selector: security_login     # your selector route
    login_ldap: security_login_ldap    # your LDAP form route
    oauth_connect: security_oauth      # your OAuth2 start route
    oauth_check: security_oauth_check  # your OAuth2 callback route
```

> **Important**: `routes.oauth_check` is critical. The `CertificateAuthenticator` compares the current request's route name against this value to decide whether it should intercept the request.
