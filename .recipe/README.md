````markdown
# PasaiakoUdalaAuthBundle-erako Symfony Flex Errezeta

Direktorio honek PasaiakoUdalaAuthBundle-aren instalazio eta konfigurazio automatikoa egiteko Symfony Flex errezeta gordetzen du.

## Zer egiten du errrezeta honek?

Erabiltzaile batek `composer require pasaia-udala/auth-bundle` exekutatzen duenean, Symfony Flex-ek automatikoki:

1. **Bundle-a erregistratuko du** `config/bundles.php` fitxategian
2. **Konfigurazio fitxategiak kopiatuko ditu** proiektuan:
   - `config/packages/pasaiako_udala_auth.yaml` - Bundle-aren konfigurazio nagusia
   - `config/packages/security.yaml` - Segurtasun konfigurazio adibidea (iruzkindurik)
   - `config/routes/pasaiako_udala_auth.yaml` - Ibilbideen adibidea (iruzkindurik)
3. **Ingurune aldagaiak gehituko ditu** `.env` fitxategira:
   - LDAP konexio parametroak (host, port, base DN, etab.)
4. **Instalazio osteko mezua erakutsiko du** hurrengo pausoekin

## Errezeta hau Symfony Recipes-en argitaratzea

Errezeta hau Symfony erabiltzaile guztientzat eskuragarri egiteko, Symfony-ren errezeta biltegi ofizialera bidali behar duzu.

### Argitaratzeko pausoak:

1. **Fork egin symfony/recipes-contrib biltegiaren**
   ```bash
   # Joan https://github.com/symfony/recipes-contrib eta egin klik "Fork"-en
   ```

2. **Klonatu zure fork-a**
   ```bash
   git clone git@github.com:YOUR_USERNAME/recipes-contrib.git
   cd recipes-contrib
   ```

3. **Sortu errzetaren direktorioa**
   ```bash
   mkdir -p pasaia-udala/auth-bundle/1.1
   ```

4. **Kopiatu errezeta fitxategiak**
   ```bash
   # Bundle honen .recipe direktoriotik, kopiatu:
   cp /path/to/PasaiakoUdalaAuthBundle/.recipe/manifest.json pasaia-udala/auth-bundle/1.1/
   cp -r /path/to/PasaiakoUdalaAuthBundle/.recipe/config pasaia-udala/auth-bundle/1.1/
   cp /path/to/PasaiakoUdalaAuthBundle/.recipe/post-install.txt pasaia-udala/auth-bundle/1.1/
   ```

5. **Commit eta push egin**
   ```bash
   git add pasaia-udala/
   git commit -m "Add recipe for pasaia-udala/auth-bundle"
   git push origin main
   ```

6. **Sortu Pull Request**
   - Joan https://github.com/symfony/recipes-contrib
   - Klik "New Pull Request"
   - Hautatu zure fork-a eta branch-a
   - Bidali PR bat deskribapen argiarekin

### Alternatiboa: Erabili Flex endpoint pribatua

Errezeta hau berehala erabili nahi baduzu PR ofiziala onartu arte itxaron gabe:

1. **Konfiguratu Flex endpoint pribatu bat** zure proiektuetan:
   ```json
   // composer.json zure aplikazioan
   {
       "extra": {
           "symfony": {
               "endpoint": [
                   "https://api.github.com/repos/ikerib/flex-recipes/contents/index.json",
                   "flex://defaults"
               ]
           }
       }
   }
   ```

2. **Sortu flex-recipes biltegi bat** errezeta egiturarekin

## Errezeta lokalean probatzea

Errezeta argitaratu aurretik probatzeko:

1. Sortu Symfony proiektu berri bat
2. Gehitu errezeta fitxategiak eskuz Flex portaera simulatzeko
3. Egiaztatu fitxategi guztiak ondo sortzen direla
4. Begiratu ingurune aldagaiak `.env` fitxategira gehitzen direla

## Errezeta egitura

```
.recipe/
├── manifest.json              # Errezeta konfigurazioa
├── post-install.txt          # Instalazioaren ostean erakusten den mezua
└── config/
    ├── packages/
    │   ├── pasaiako_udala_auth.yaml  # Bundle-aren konfigurazioa
    │   └── security.yaml              # Segurtasun konfigurazioa (iruzkindurik)
    └── routes/
        └── pasaiako_udala_auth.yaml   # Ibilbideak (iruzkindurik)
```

## Informazio gehiago

- [Symfony Flex dokumentazioa](https://symfony.com/doc/current/setup/flex.html)
- [Errezetak nola ekarri](https://github.com/symfony/recipes-contrib/blob/main/CONTRIBUTING.md)
- [Errezeta formatuaren erreferentzia](https://github.com/symfony/recipes/blob/main/README.rst)

````
