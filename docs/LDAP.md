
# LDAP konfigurazioa

LDAP / Active Directory autentifikazioa konfiguratzeko gida zehatza.

## Konfigurazio-egitura

```yaml
# config/packages/pasaiako_udala_auth.yaml
pasaiako_udala_auth:
  server:
    host: 'LDAP_SERVER_IP'              # LDAP zerbitzariaren IP edo hostname
    port: 389                         # 389 (TLS gabe edo STARTTLS) edo 636 (TLSarekin)
    encryption: 'none'                # none, ssl edo tls
    base_dn: 'dc=domain,dc=net'       # Bilaketetarako base DN
    # DN pattern to bind users (use {username})
    user_dn_pattern: 'uid={username},ou=users,dc=domain,dc=net'
    # Optional: bind/service account used for searches
    bind_dn: 'cn=ServiceAccount,ou=ServiceAccounts,dc=domain,dc=net'
    bind_password: '%env(LDAP_SERVICE_PASSWORD)%'
    group_search_filter: '(member={user_dn})'
        
    role_mapping:
        'CN=Developers,OU=Groups,DC=domain,DC=net': 'ROLE_DEVELOPER'
        'CN=Admins,OU=Groups,DC=domain,DC=net': 'ROLE_ADMIN'
        'CN=Users,OU=Groups,DC=domain,DC=net': 'ROLE_USER'
        
    default_role: 'ROLE_USER'
    
  group_search:
    enabled: true
    base_dn: null
    filter: '(member={user_dn})'
    recursive: true
```

## Zerbitzariaren parametroak

### `host`
- LDAP/AD zerbitzariaren IP edo hostname
- Adibidea: `'192.168.0.1'` edo `'ldap.domain.net'`

### `port`
- `389`: TLS gabeko LDAP edo STARTTLS bidezko konexioa
- `636`: LDAPS (LDAP SSL/TLS bidez)
- Gomendagarria: 636 produkzioan

### `base_dn`
- Bilaketetarako oinarrizko Distinguished Name
- Formatua: `'dc=domain,dc=ext'`
- Adibidea: `'dc=domain,dc=net'` (domain.net domeinurako)

### `bind_dn`
- Zerbitzu edo bind kontua LDAP bilaketak egiteko (aukera).
- Taldeen bilaketa errekurtsiborako beharrezkoa izan daiteke.
- Formatua: `'cn=KontuIzena,ou=UnitateOrg,dc=domeinu,dc=ext'`
- Adibidea: `'cn=ServiceAccount,ou=ServiceAccounts,dc=domain,dc=net'`

### `bind_password`
- Zerbitzu-kontuaren pasahitza (ingurune aldagaian gordetzea gomendatzen da)
- Adibidea: `'%env(LDAP_SERVICE_PASSWORD)%'`

### `user_dn_pattern`
- Erabiltzailearen DN era definitzen duen patroi bat; normalean `uid={username},ou=users,dc=...` bezalakoa da.
- Erabiltzailearen autentifikaziorako erabiltzen da (bind bidez).
- Adibidea: `uid={username},ou=users,dc=domain,dc=net`

### `group_search_filter`
- Erabiltzailearen taldeak bilatzeko iragazkia
- Placeholders:
  - `{dn}`: erabiltzailearen Distinguished Name
- Active Directory (errekurtsiboa): `'(member:1.2.840.113556.1.4.1941:={dn})'`
  - Iragazki honek automatikoki barneko taldeak ere barne hartzen ditu
- OpenLDAP: `'(memberUid={username})'`

## Rol mapaketa

Rol mapaketak LDAP taldeak Symfony roletara bihurtzen ditu.

