# Troubleshooting

Common problems and their solutions.

---

## LDAP

### "Invalid credentials" on login

**Cause**: Username or password don't match LDAP, or the DN pattern is incorrect.

**Solution**:

1. Verify you can connect to the LDAP server from your machine:
   ```bash
   ldapsearch -x -H ldap://192.168.1.10:389 \
     -D "uid=myuser,ou=users,dc=mydomain,dc=net" \
     -w "mypassword" \
     -b "dc=mydomain,dc=net" \
     "(sAMAccountName=myuser)"
   ```

2. Check that `user_dn_pattern` is correct. The bundle replaces `{username}` with the form value:
   ```yaml
   # If your DN is: uid=iibarguren,ou=users,dc=pasaia,dc=net
   user_dn_pattern: 'uid={username},ou=users,dc=pasaia,dc=net'

   # If you use Active Directory with UPN:
   user_dn_pattern: '{username}@pasaia.net'
   ```

3. Verify that `base_dn` is correct:
   ```yaml
   base_dn: 'dc=pasaia,dc=net'
   ```

### "Could not connect to LDAP server"

**Cause**: No connectivity to the LDAP server.

**Solution**:

```bash
# Check connectivity
nc -zv 192.168.1.10 389

# If using Docker, check from inside the container
docker exec -it my_container nc -zv 192.168.1.10 389
```

- Verify the port is correct (389 or 636)
- Verify no firewall is blocking the connection
- If using `encryption: tls`, make sure the server supports STARTTLS

### No roles assigned (user only has ROLE_USER)

**Cause**: LDAP groups don't match those configured in `role_mapping`.

**Solution**:

1. Check that `group_search.enabled` is `true`

2. Verify the user's groups in LDAP:
   ```bash
   ldapsearch -x -H ldap://192.168.1.10:389 \
     -D "cn=ServiceAccount,ou=ServiceAccounts,dc=pasaia,dc=net" \
     -w "password" \
     -b "dc=pasaia,dc=net" \
     "(member=uid=myuser,ou=users,dc=pasaia,dc=net)" \
     cn
   ```

3. Check that the names in `role_mapping` match the group CN (comparison is case-insensitive):
   ```yaml
   role_mapping:
       informatika: ROLE_ADMIN    # must match the group CN
   ```

4. Verify that `bind_dn` and `bind_password` are correct (group search requires an account with read permissions)

### User attributes are empty

**Cause**: The service account doesn't have permission to read those attributes, or the attribute names don't match the schema.

**Solution**:

1. Check which attributes are available for the user:
   ```bash
   ldapsearch -x -H ldap://192.168.1.10:389 \
     -D "cn=ServiceAccount,..." -w "password" \
     -b "dc=pasaia,dc=net" \
     "(sAMAccountName=myuser)" \
     department displayName mail
   ```

2. Adjust `user_attributes` to match the attributes that exist in your LDAP schema.

---

## Certificate (OAuth2 / Izenpe)

### "Invalid redirect_uri" from Izenpe

**Cause**: The callback URL registered in Izenpe doesn't match your application's URL.

**Solution**:

1. Verify the exact URL of your `oauth_check` route:
   ```bash
   php bin/console debug:router oauth_check
   ```

2. The URL registered in Izenpe must be an exact match. Example:
   ```
   https://yourapp.pasaia.net/connect/giltza/check
   ```

3. In production it must be HTTPS.

### "Invalid client credentials"

**Cause**: `CLIENT_ID` or `CLIENT_SECRET` are incorrect.

**Solution**: Verify the environment variables in `.env.local` and compare with the credentials provided by Izenpe.

### Callback arrives but doesn't authenticate

**Cause**: `CertificateAuthenticator` is not registered in `security.yaml`.

**Solution**: Make sure it is in the `custom_authenticators` list:
```yaml
custom_authenticators:
    - PasaiaUdala\AuthBundle\Security\LdapAuthenticator
    - PasaiaUdala\AuthBundle\Security\CertificateAuthenticator
```

### Certificate authenticates but user only has ROLE_USER

**Cause**: The certificate's DNI is not found in LDAP, or the DNI field doesn't match.

**Solution**:

1. Verify that the user has the `employeeID` field (or the field configured in `dni_field`) populated in LDAP:
   ```bash
   ldapsearch -x -H ldap://192.168.1.10:389 \
     -D "cn=ServiceAccount,..." -w "password" \
     -b "dc=pasaia,dc=net" \
     "(employeeID=12345678X)"
   ```

2. If you use a different field, configure it:
   ```yaml
   pasaiako_udala_auth:
       dni_field: extensionAttribute1
   ```

3. If the user doesn't exist in LDAP, the bundle assigns `ROLE_USER` with the certificate data. This is expected behavior.

---

## General

### Bundle doesn't load (no services or routes appear)

**Cause**: `server.host` is not configured. The bundle automatically disables itself when there is no host.

**Solution**: Configure `server.host` in `pasaiako_udala_auth.yaml`:
```yaml
pasaiako_udala_auth:
    server:
        host: '192.168.1.10'
```

### Error "There is no extension able to load the configuration for pasaiako_udala_auth"

**Cause**: The bundle is not registered.

**Solution**: Add it in `config/bundles.php`:
```php
PasaiaUdala\AuthBundle\PasaiaUdalaAuthBundle::class => ['all' => true],
```

### Views not found (@PasaiakoUdalaAuth)

**Cause**: The bundle's Twig namespace is not registered.

**Solution**: This is registered automatically by the bundle Extension. If it still fails, verify it manually:
```bash
php bin/console debug:twig
```

You should see `@PasaiakoUdalaAuth` pointing to the bundle's `Resources/views` folder.

---

## Debug

To get more information in logs, configure monolog for the `security` channel:

```yaml
# config/packages/monolog.yaml (in dev)
monolog:
    handlers:
        security:
            type: stream
            path: '%kernel.logs_dir%/security.log'
            level: debug
            channels: ['security']
```

The `LdapClient` logs all operations (connection, bind, searches, role mapping) via the injected logger.
