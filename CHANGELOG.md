# Aldaketak (Changelog)

Proiektu honetako aldaketa garrantzitsuenak fitxategi honetan dokumentatuko dira.

Formatoa oinarrian [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) dago,
eta proiektu honek [Semantic Versioning](https://semver.org/spec/v2.0.0.html)-en arauak jarraitzen ditu.

## [1.1.0] - 2024-01-XX

### Gehitu da
- Ziurtagiri bidezko autentifikazioa Izenpe OAuth2 (Giltza) bidez
- LDAP bilaketa automatikoa DNIaren (employeeID eremua) arabera
- Ikusizko autentifikazio-metodoen hautatzailea
- Ziurtagiriaren datuen gordetzea `LdapUser`-en
- Ziurtagiri eta LDAP autentifikazioen arteko integrazioa

### Konponduta
- `LdapAuthenticator`-eko `LOGIN_ROUTE` konstantea eguneratua (orain 'app_login_ldap')

## [1.0.0] - 2024-01-XX

### Gehitu da
- Hasierako bertsioa
- LDAP autentifikazioa, Active Directory laguntzarekin
- Taldeen bilaketa errekurtsiboa service account-arekin
- Rol mapaketa (LDAP taldeak → Symfony rolen mapaketa)
- YAML bidez konfigura daiteke
- `LdapUser` entitatea
- `LdapUserProvider`
- `LdapAuthenticator`
- `LdapClient` zerbitzua
