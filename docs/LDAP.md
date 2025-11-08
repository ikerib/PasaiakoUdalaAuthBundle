
# LDAP konfigurazioa

LDAP / Active Directory autentifikazioa konfiguratzeko gida zehatza.

## Konfigurazio-egitura

```yaml
# config/packages/pasaiako_udala_auth.yaml
pasaiako_udala_auth:
    server:
        host: 'LDAP_SERVER_IP'              # LDAP zerbitzariaren IP edo hostname
        port: 389                         # 389 (TLS gabe edo STARTTLS) edo 636 (TLSarekin)
        base_dn: 'dc=domain,dc=net'       # Bilaketetarako base DN
        service_account_dn: 'cn=ServiceAccount,ou=ServiceAccounts,dc=domain,dc=net'
        service_account_password: '%env(LDAP_SERVICE_PASSWORD)%'
        user_search_filter: '(sAMAccountName={username})'
        group_search_filter: '(member:1.2.840.113556.1.4.1941:={dn})'
        
    role_mapping:
        'CN=Developers,OU=Groups,DC=domain,DC=net': 'ROLE_DEVELOPER'
        'CN=Admins,OU=Groups,DC=domain,DC=net': 'ROLE_ADMIN'
        'CN=Users,OU=Groups,DC=domain,DC=net': 'ROLE_USER'
        
    default_role: 'ROLE_USER'
    
    group_search:
        enabled: true
        use_service_account: true
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

### `service_account_dn`
- Direktorioan irakurketa-baimenak dituen zerbitzu-kontua
- Taldeen bilaketa errekurtsiborako beharrezkoa
- Formatua: `'cn=KontuIzena,ou=UnitateOrg,dc=domeinu,dc=ext'`
- Adibidea: `'cn=ServiceAccount,ou=ServiceAccounts,dc=domain,dc=net'`

### `service_account_password`
- Zerbitzu-kontuaren pasahitza
- Garrantzitsua: ingurune aldagai baten bidez erabili, ez kodean gogoratu
- Adibidea: `'%env(LDAP_SERVICE_PASSWORD)%'`

### `user_search_filter`
- Erabiltzaileak bilatzeko LDAP iragazkia
- Erabilgarri dauden placeholders:
  - `{username}`: loginean sartutako erabiltzaile izena
  - `{base_dn}`: konfiguratutako base DN
- Adibideak:
  - Active Directory: `'(sAMAccountName={username})'`
  - OpenLDAP: `'(uid={username})'`
  - Posta bidez: `'(mail={username})'`

### `group_search_filter`
- Erabiltzailearen taldeak bilatzeko iragazkia
- Placeholders:
  - `{dn}`: erabiltzailearen Distinguished Name
- Active Directory (errekurtsiboa): `'(member:1.2.840.113556.1.4.1941:={dn})'`
  - Iragazki honek automatikoki barneko taldeak ere barne hartzen ditu
- OpenLDAP: `'(memberUid={username})'`

## Rol mapaketa

Rol mapaketak LDAP taldeak Symfony roletara bihurtzen ditu.

