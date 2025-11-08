<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Service;

use Psr\Log\LoggerInterface;

/**
 * LdapClient - Service for LDAP authentication and group search
 *
 * Features:
 * - Connect to LDAP server
 * - Authenticate user with username/password
 * - Search user groups recursively
 * - Map LDAP groups to Symfony roles
 */
class LdapClient
{
    private $connection = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $encryption,
        private readonly string $baseDn,
        private readonly string $userDnPattern,
        private readonly ?string $bindDn,
        private readonly ?string $bindPassword,
        private readonly array $roleMapping,
        private readonly string $defaultRole,
        private readonly bool $groupSearchEnabled,
        private readonly ?string $groupSearchBaseDn,
        private readonly string $groupSearchFilter,
        private readonly bool $groupSearchRecursive,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Authenticate user against LDAP server
     *
     * @param string $username Username (without DN)
     * @param string $password User password
     * @return bool True if authentication successful
     */
    public function authenticate(string $username, string $password): bool
    {
        try {
            $this->connect();

            // Build user DN
            $userDn = str_replace('{username}', $username, $this->userDnPattern);

            $this->logger->info('LDAP: Intentando autenticar usuario', [
                'username' => $username,
                'user_dn' => $userDn
            ]);

            // Try to bind with user credentials
            $bind = @ldap_bind($this->connection, $userDn, $password);

            if (!$bind) {
                $this->logger->warning('LDAP: Autenticación fallida', [
                    'username' => $username,
                    'error' => ldap_error($this->connection)
                ]);
                return false;
            }

            $this->logger->info('LDAP: Autenticación exitosa', ['username' => $username]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('LDAP: Error de autenticación', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get user's groups from LDAP (with recursive search)
     *
     * @param string $username Username
     * @param string|null $password User password (for authenticated search in AD)
     * @return array Array of group CNs
     */
    public function getUserGroups(string $username, ?string $password = null): array
    {
        if (!$this->groupSearchEnabled) {
            $this->logger->debug('LDAP: Búsqueda de grupos deshabilitada');
            return [];
        }

        try {
            $this->connect();

            $userDn = str_replace('{username}', $username, $this->userDnPattern);

            // If password provided, bind as user (for Active Directory)
            // Otherwise use service account bind
            if ($password !== null) {
                $this->logger->debug('LDAP: Bind como usuario para búsqueda de grupos', ['username' => $username]);
                $bind = @ldap_bind($this->connection, $userDn, $password);
                if (!$bind) {
                    throw new \RuntimeException('No se pudo hacer bind como usuario: ' . ldap_error($this->connection));
                }
            } else {
                $this->bindForSearch();
            }

            $baseDn = $this->groupSearchBaseDn ?? $this->baseDn;

            $this->logger->info('LDAP: Buscando grupos del usuario', [
                'username' => $username,
                'user_dn' => $userDn,
                'base_dn' => $baseDn
            ]);

            $groups = $this->searchGroupsRecursive($userDn, $baseDn, $username);

            $this->logger->info('LDAP: Grupos encontrados', [
                'username' => $username,
                'groups' => $groups,
                'count' => count($groups)
            ]);

            return $groups;

        } catch (\Exception $e) {
            $this->logger->error('LDAP: Error al buscar grupos', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Find user by DNI in LDAP (searches in 'employeeID' field)
     *
     * @param string $dni User DNI
     * @return array|null User data with 'username' and 'groups', or null if not found
     */
    public function findUserByDni(string $dni): ?array
    {
        try {
            $this->connect();
            $this->bindForSearch();

            $baseDn = $this->groupSearchBaseDn ?? $this->baseDn;

            // Search for user by DNI in 'employeeID' field
            $filter = sprintf('(employeeID=%s)', ldap_escape($dni, '', LDAP_ESCAPE_FILTER));

            $this->logger->info('LDAP: Buscando usuario por DNI', [
                'dni' => $dni,
                'base_dn' => $baseDn,
                'filter' => $filter
            ]);

            $search = @ldap_search($this->connection, $baseDn, $filter, ['sAMAccountName', 'userPrincipalName', 'cn', 'memberOf', 'employeeID']);

            if (!$search) {
                $this->logger->warning('LDAP: Error al buscar usuario por DNI', [
                    'dni' => $dni,
                    'error' => ldap_error($this->connection)
                ]);
                return null;
            }

            $entries = ldap_get_entries($this->connection, $search);

            if ($entries['count'] === 0) {
                $this->logger->info('LDAP: Usuario no encontrado por DNI', ['dni' => $dni]);
                return null;
            }

            $entry = $entries[0];

            // Extract username (prefer sAMAccountName for AD)
            $username = $entry['samaccountname'][0] ?? $entry['userprincipalname'][0] ?? $entry['cn'][0] ?? null;

            if (!$username) {
                $this->logger->warning('LDAP: Usuario encontrado pero sin username válido', ['dni' => $dni]);
                return null;
            }

            // Extract groups from memberOf attribute
            $groups = [];
            if (isset($entry['memberof'])) {
                for ($j = 0; $j < $entry['memberof']['count']; $j++) {
                    $groupDn = $entry['memberof'][$j];

                    // Extract CN from group DN
                    if (preg_match('/^CN=([^,]+)/', $groupDn, $matches)) {
                        $groups[] = $matches[1];
                    }
                }
            }

            $this->logger->info('LDAP: Usuario encontrado por DNI', [
                'dni' => $dni,
                'username' => $username,
                'groups' => $groups
            ]);

            return [
                'username' => $username,
                'groups' => $groups
            ];

        } catch (\Exception $e) {
            $this->logger->error('LDAP: Error al buscar usuario por DNI', [
                'dni' => $dni,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Map LDAP groups to Symfony roles
     *
     * @param array $groups Array of LDAP group CNs
     * @return array Array of Symfony roles
     */
    public function mapGroupsToRoles(array $groups): array
    {
        $roles = [$this->defaultRole]; // Always include default role

        foreach ($groups as $group) {
            // Normalize group name (lowercase)
            $groupNormalized = strtolower($group);

            // Check if group has a mapped role
            foreach ($this->roleMapping as $ldapGroup => $role) {
                if (strtolower($ldapGroup) === $groupNormalized) {
                    $roles[] = $role;
                    $this->logger->debug('LDAP: Grupo mapeado a rol', [
                        'group' => $group,
                        'role' => $role
                    ]);
                }
            }
        }

        // Remove duplicates and return
        $roles = array_unique($roles);

        $this->logger->info('LDAP: Roles finales asignados', [
            'groups' => $groups,
            'roles' => $roles
        ]);

        return $roles;
    }

    /**
     * Connect to LDAP server
     */
    private function connect(): void
    {
        if ($this->connection !== null) {
            return; // Already connected
        }

        // Build LDAP URI
        $protocol = $this->encryption === 'ssl' ? 'ldaps' : 'ldap';
        $uri = sprintf('%s://%s:%d', $protocol, $this->host, $this->port);

        $this->logger->debug('LDAP: Conectando al servidor', ['uri' => $uri]);

        $this->connection = ldap_connect($uri);

        if (!$this->connection) {
            throw new \RuntimeException('No se pudo conectar al servidor LDAP');
        }

        // Set LDAP options
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, 10);

        // Enable TLS if needed
        if ($this->encryption === 'tls') {
            if (!@ldap_start_tls($this->connection)) {
                throw new \RuntimeException('No se pudo iniciar TLS: ' . ldap_error($this->connection));
            }
        }

        $this->logger->info('LDAP: Conexión establecida');
    }

    /**
     * Bind to LDAP for search operations
     */
    private function bindForSearch(): void
    {
        if ($this->bindDn && $this->bindPassword) {
            $this->logger->debug('LDAP: Bind con DN específico', ['bind_dn' => $this->bindDn]);

            $bind = @ldap_bind($this->connection, $this->bindDn, $this->bindPassword);

            if (!$bind) {
                throw new \RuntimeException('No se pudo hacer bind para búsqueda: ' . ldap_error($this->connection));
            }
        } else {
            // Anonymous bind
            $this->logger->debug('LDAP: Bind anónimo');

            $bind = @ldap_bind($this->connection);

            if (!$bind) {
                throw new \RuntimeException('No se pudo hacer bind anónimo: ' . ldap_error($this->connection));
            }
        }
    }

    /**
     * Search groups recursively
     *
     * @param string $userDn User DN
     * @param string $baseDn Base DN for search
     * @param string|null $username Username (for sAMAccountName filter in AD)
     * @param array $foundGroups Already found groups (for recursion)
     * @return array Array of group CNs
     */
    private function searchGroupsRecursive(string $userDn, string $baseDn, ?string $username = null, array $foundGroups = []): array
    {
        // Build search filter - replace placeholders
        $filter = str_replace('{user_dn}', $userDn, $this->groupSearchFilter);
        if ($username !== null) {
            $filter = str_replace('{username}', $username, $filter);
        }

        $this->logger->debug('LDAP: Buscando grupos', [
            'base_dn' => $baseDn,
            'filter' => $filter
        ]);

        // Search for user and get memberOf attribute (Active Directory style)
        $search = @ldap_search($this->connection, $baseDn, $filter, ['cn', 'memberOf', 'sAMAccountName']);

        if (!$search) {
            $this->logger->warning('LDAP: Error en búsqueda de grupos', [
                'error' => ldap_error($this->connection)
            ]);
            return $foundGroups;
        }

        $entries = ldap_get_entries($this->connection, $search);

        if ($entries['count'] === 0) {
            return $foundGroups;
        }

        // Process entries - for Active Directory, get memberOf attribute
        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];

            // Active Directory: memberOf attribute contains all groups
            if (isset($entry['memberof'])) {
                for ($j = 0; $j < $entry['memberof']['count']; $j++) {
                    $groupDn = $entry['memberof'][$j];

                    // Extract CN from group DN (e.g., "CN=Domain Admins,CN=Users,DC=pasaia,DC=net")
                    if (preg_match('/^CN=([^,]+)/', $groupDn, $matches)) {
                        $groupCn = $matches[1];

                        // Skip if already found
                        if (in_array($groupCn, $foundGroups, true)) {
                            continue;
                        }

                        $foundGroups[] = $groupCn;
                        $this->logger->debug('LDAP: Grupo encontrado', ['group' => $groupCn]);

                        // Recursive search if enabled (for nested groups)
                        if ($this->groupSearchRecursive) {
                            $foundGroups = $this->searchGroupsRecursive($groupDn, $baseDn, null, $foundGroups);
                        }
                    }
                }
            }

            // Fallback: traditional group search by CN
            elseif (isset($entry['cn'][0])) {
                $groupCn = $entry['cn'][0];

                // Skip if already found
                if (in_array($groupCn, $foundGroups, true)) {
                    continue;
                }

                $foundGroups[] = $groupCn;
                $this->logger->debug('LDAP: Grupo encontrado', ['group' => $groupCn]);
            }
        }

        return $foundGroups;
    }

    /**
     * Disconnect from LDAP server
     */
    public function disconnect(): void
    {
        if ($this->connection !== null) {
            @ldap_close($this->connection);
            $this->connection = null;
            $this->logger->debug('LDAP: Conexión cerrada');
        }
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
