# Arazoen konponketa (Troubleshooting)

PasaiakoUdalaAuthBundle erabiltzerakoan sortzen diren arazo ohikoen konponbideak.

## LDAP autentifikazio arazoak

### "Invalid credentials"

**Sintoma**: Erabiltzaile/pasahitz zuzena emanda ere saioa hastean errorea agertzen da

**Arrazoi posibleak**:

1. **Erabiltzailea ez dago LDAP-en**
   ```bash
   # ldapsearch bidez egiaztatzeko
   ldapsearch -x -H ldap://172.28.64.20:389 \
     -D "cn=ServiceAccount,ou=ServiceAccounts,dc=pasaia,dc=net" \
     -w "password" \
     -b "dc=pasaia,dc=net" \
     "(sAMAccountName=usuario)"
   ```

2. **DN patroi okerra edo `user_dn_pattern` gaizki konfiguratuta**
   ```yaml
   # config/packages/pasaiako_udala_auth.yaml fitxategian egiaztatu
   pasaiako_udala_auth:
     server:
       user_dn_pattern: 'uid={username},ou=users,dc=domain,dc=net'
   ```

3. **Base DN okerra da**
   ```yaml
   pasaiako_udala_auth:
     server:
       base_dn: 'dc=pasaia,dc=net'
   ```

# Arazoen konponketa (Troubleshooting)

PasaiakoUdalaAuthBundle erabiltzerakoan sortzen diren arazo ohikoen konponbideak.

## LDAP autentifikazio arazoak

### "Invalid credentials"

**Sintoma**: Erabiltzaile/pasahitz zuzena emanda ere saioa hastean errorea agertzen da

**Arrazoi posibleak**:

1. **Erabiltzailea ez dago LDAP-en**
   ```bash
   # ldapsearch bidez egiaztatzeko
   ldapsearch -x -H ldap://172.28.64.20:389 \
     -D "cn=ServiceAccount,ou=ServiceAccounts,dc=pasaia,dc=net" \
     -w "password" \
     -b "dc=pasaia,dc=net" \
     "(sAMAccountName=usuario)"
   ```

2. **DN patroi okerra edo `user_dn_pattern` gaizki konfiguratuta**
   ```yaml
   # config/packages/pasaiako_udala_auth.yaml fitxategian egiaztatu
   pasaiako_udala_auth:
     server:
       user_dn_pattern: 'uid={username},ou=users,dc=domain,dc=net'
   ```

3. **Base DN okerra da**
   ```yaml
   pasaiako_udala_auth:
     server:
       base_dn: 'dc=pasaia,dc=net'
   ```

4. **Pasahitzak karaktere bereziak ditu**
   - LDAP sarritan karaktere batzuk onartzen ez ditu edo escapeari eskatzen dio
   - Lehen neurrian, probatu pasahitz sinpleago batekin

### "Could not connect to LDAP server"

**Sintoma**: LDAP zerbitzarira konektatzerakoan errorea

**Konponbideak**:

1. **Konektibitatea egiaztatu**
   ```bash
   # Edukiontzitik edo zerbitzaritik
   telnet 172.28.64.20 389
   # Edo nc erabiliz
   nc -zv 172.28.64.20 389
   ```
