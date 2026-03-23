# PasaiaUdalaAuthBundle

Symfony-rako autentikazio bikoitzerako bundlea: **LDAP** (Active Directory) + **Ziurtagiria** (Izenpe OAuth2).

## 📋 Ezaugarriak

- ✅ **LDAP autentifikazioa**: Active Directory-rekin bateragarria
- ✅ **Ziurtagirien bidezko autentifikazioa**: Izenpe integrazioa (Giltza OAuth2)
- ✅ **Ikusizko hautatzailea**: autentifikazio-metodoa aukeratzeko orria
- ✅ **Taldeen bilaketa**: errekurtsiboa service account-arekin
- ✅ **Rol mapaketa**: LDAP taldeak → Symfony rolen mapaketa
- ✅ **Integrazio automatikoa**: ziurtagiriak erabiltzailea LDAP-en bilatzen du NANaren bidez
- ✅ **Erabat konfigura daiteke**: YAML bidez

## 🚀 Instalazioa

```bash
composer require pasaia-udala/auth-bundle
```

## ⚙️ Konfigurazio azkarra

Ikusi dokumentazio osoa: [docs/INSTALLATION.md](docs/INSTALLATION.md)

## 📖 Dokumentazioa

- [Instalazio osoa](docs/INSTALLATION.md)
- [LDAP konfigurazioa](docs/LDAP.md)
- [Izenpe konfigurazioa](docs/IZENPE.md)
- [Arazoen konpontzea (Troubleshooting)](docs/TROUBLESHOOTING.md)

## 📝 Eskakizunak

- PHP >= 8.2
- `ext-ldap` luzapena
- Symfony 6.4 edo 7.x
- KnpU OAuth2 Client Bundle
- Izenpe (Giltza) OAuth2 bezeroa

## 📄 Lizentzia

MIT

## 👥 Egilea

Garatu du **Pasaia Udala**-k (Informatika Saila)

**Bertsioa**: 1.1.0
