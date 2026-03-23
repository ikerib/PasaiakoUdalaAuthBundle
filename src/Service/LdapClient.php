<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

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
    private ?\LDAP\Connection $connection = null;

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
        private readonly LoggerInterface $logger,
        private readonly string $dniField = 'employeeID',
        private readonly array $userAttributes = ['department', 'displayName', 'extensionName', 'mail', 'preferredLanguage', 'description'],
        private readonly int $cacheTtl = 0,
        private readonly ?CacheInterface $cache = null,
    ) {
    }

    /**
     * Authenticate user against LDAP server
     *
     * @param string $username Username (without DN)
     * @param string $password User password
     * @return bool True if authentication successful
     */
    public function authenticate(string $username, #[\SensitiveParameter] string $password): bool
    {
        try {
            $this->connect();

            $userDn = str_replace('{username}', $username, $this->userDnPattern);

            $this->logger->info('LDAP: Authenticating user', [
                'username' => $username,
                'user_dn' => $userDn
            ]);

            $bind = @ldap_bind($this->connection, $userDn, $password);

            if (!$bind) {
                $this->logger->warning('LDAP: Authentication failed', [
                    'username' => $username,
                    'error' => ldap_error($this->connection)
                ]);
                return false;
            }

            $this->logger->info('LDAP: Authentication successful', ['username' => $username]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('LDAP: Authentication error', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return false;
        } finally {
            // Reset connection so it is not left bound as the authenticated user
            $this->disconnect();
        }
    }

    /**
     * Get user's groups from LDAP (with recursive search)
     *
     * @param string $username Username
     * @param string|null $password User password (for authenticated search in AD)
     * @return array Array of group CNs
     */
    public function getUserGroups(string $username, #[\SensitiveParameter] ?string $password = null): array
    {
        if (!$this->groupSearchEnabled) {
            $this->logger->debug('LDAP: Group search disabled');
            return [];
        }

        // Use cache if available and no password provided (password means fresh bind)
        if ($this->cacheTtl > 0 && $this->cache !== null && $password === null) {
            $cacheKey = 'ldap_groups_' . md5($username);
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($username) {
                $item->expiresAfter($this->cacheTtl);
                return $this->fetchUserGroups($username, null);
            });
        }

        return $this->fetchUserGroups($username, $password);
    }

    private function fetchUserGroups(string $username, #[\SensitiveParameter] ?string $password): array
    {
        try {
            $this->connect();

            $userDn = str_replace('{username}', $username, $this->userDnPattern);

            if ($password !== null) {
                $this->logger->debug('LDAP: Binding as user for group search', ['username' => $username]);
                $bind = @ldap_bind($this->connection, $userDn, $password);
                if (!$bind) {
                    throw new \RuntimeException('Failed to bind as user: ' . ldap_error($this->connection));
                }
            } else {
                $this->bindForSearch();
            }

            $baseDn = $this->groupSearchBaseDn ?? $this->baseDn;

            $this->logger->info('LDAP: Searching user groups', [
                'username' => $username,
                'user_dn' => $userDn,
                'base_dn' => $baseDn
            ]);

            $groups = $this->searchGroupsRecursive($userDn, $baseDn, $username);

            $this->logger->info('LDAP: Groups found', [
                'username' => $username,
                'groups' => $groups,
                'count' => count($groups)
            ]);

            return $groups;

        } catch (\Exception $e) {
            $this->logger->error('LDAP: Error searching groups', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Find user by DNI in LDAP
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

            $filter = sprintf('(%s=%s)', $this->dniField, ldap_escape($dni, '', LDAP_ESCAPE_FILTER));

            $this->logger->info('LDAP: Searching user by DNI', [
                'dni' => $dni,
                'base_dn' => $baseDn,
                'filter' => $filter
            ]);

            $search = @ldap_search($this->connection, $baseDn, $filter, ['sAMAccountName', 'userPrincipalName', 'cn', 'memberOf', $this->dniField]);

            if (!$search) {
                $this->logger->warning('LDAP: Error searching user by DNI', [
                    'dni' => $dni,
                    'error' => ldap_error($this->connection)
                ]);
                return null;
            }

            $entries = ldap_get_entries($this->connection, $search);

            if ($entries['count'] === 0) {
                $this->logger->info('LDAP: User not found by DNI', ['dni' => $dni]);
                return null;
            }

            $entry = $entries[0];

            // Extract username (prefer sAMAccountName for AD)
            $username = $entry['samaccountname'][0] ?? $entry['userprincipalname'][0] ?? $entry['cn'][0] ?? null;

            if (!$username) {
                $this->logger->warning('LDAP: User found but no valid username', ['dni' => $dni]);
                return null;
            }

            // Extract groups from memberOf attribute
            $groups = [];
            if (isset($entry['memberof'])) {
                for ($j = 0; $j < $entry['memberof']['count']; $j++) {
                    $groupDn = $entry['memberof'][$j];

                    if (preg_match('/^CN=([^,]+)/', $groupDn, $matches)) {
                        $groups[] = $matches[1];
                    }
                }
            }

            $this->logger->info('LDAP: User found by DNI', [
                'dni' => $dni,
                'username' => $username,
                'groups' => $groups
            ]);

            return [
                'username' => $username,
                'groups' => $groups
            ];

        } catch (\Exception $e) {
            $this->logger->error('LDAP: Error searching user by DNI', [
                'dni' => $dni,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get user attributes from LDAP
     *
     * @param string $username Username
     * @return array Array of user attributes
     */
    public function getUserAttributes(string $username): array
    {
        if ($this->cacheTtl > 0 && $this->cache !== null) {
            $cacheKey = 'ldap_attrs_' . md5($username);
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($username) {
                $item->expiresAfter($this->cacheTtl);
                return $this->fetchUserAttributes($username);
            });
        }

        return $this->fetchUserAttributes($username);
    }

    private function fetchUserAttributes(string $username): array
    {
        try {
            $this->connect();
            $this->bindForSearch();

            $baseDn = $this->groupSearchBaseDn ?? $this->baseDn;

            $filter = sprintf('(sAMAccountName=%s)', ldap_escape($username, '', LDAP_ESCAPE_FILTER));

            $this->logger->info('LDAP: Fetching user attributes', [
                'username' => $username,
                'base_dn' => $baseDn,
                'filter' => $filter
            ]);

            $search = @ldap_search($this->connection, $baseDn, $filter, $this->userAttributes);

            if (!$search) {
                $this->logger->warning('LDAP: Error fetching user attributes', [
                    'username' => $username,
                    'error' => ldap_error($this->connection)
                ]);
                return [];
            }

            $entries = ldap_get_entries($this->connection, $search);

            if ($entries['count'] === 0) {
                $this->logger->info('LDAP: User not found for attribute lookup', ['username' => $username]);
                return [];
            }

            $entry = $entries[0];
            $userAttributes = [];

            foreach ($this->userAttributes as $attribute) {
                $attributeLower = strtolower($attribute);
                if (isset($entry[$attributeLower][0])) {
                    $userAttributes[$attribute] = $entry[$attributeLower][0];
                } else {
                    $userAttributes[$attribute] = null;
                }
            }

            $this->logger->info('LDAP: Attributes fetched', [
                'username' => $username,
                'attributes' => array_keys(array_filter($userAttributes))
            ]);

            return $userAttributes;

        } catch (\Exception $e) {
            $this->logger->error('LDAP: Error fetching user attributes', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return [];
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
        $roles = [$this->defaultRole];

        foreach ($groups as $group) {
            $groupNormalized = strtolower($group);

            foreach ($this->roleMapping as $ldapGroup => $role) {
                if (strtolower($ldapGroup) === $groupNormalized) {
                    $roles[] = $role;
                    $this->logger->debug('LDAP: Group mapped to role', [
                        'group' => $group,
                        'role' => $role
                    ]);
                }
            }
        }

        $roles = array_unique($roles);

        $this->logger->info('LDAP: Roles assigned', [
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
            return;
        }

        $protocol = $this->encryption === 'ssl' ? 'ldaps' : 'ldap';
        $uri = sprintf('%s://%s:%d', $protocol, $this->host, $this->port);

        $this->logger->debug('LDAP: Connecting to server', ['uri' => $uri]);

        $this->connection = ldap_connect($uri);

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, 10);

        if ($this->encryption === 'tls') {
            if (!@ldap_start_tls($this->connection)) {
                throw new \RuntimeException('Could not start TLS: ' . ldap_error($this->connection));
            }
        }

        $this->logger->info('LDAP: Connection established');
    }

    /**
     * Bind to LDAP for search operations
     */
    private function bindForSearch(): void
    {
        if ($this->bindDn && $this->bindPassword) {
            $this->logger->debug('LDAP: Binding with service account', ['bind_dn' => $this->bindDn]);

            $bind = @ldap_bind($this->connection, $this->bindDn, $this->bindPassword);

            if (!$bind) {
                throw new \RuntimeException('Failed to bind for search: ' . ldap_error($this->connection));
            }
        } else {
            $this->logger->debug('LDAP: Anonymous bind');

            $bind = @ldap_bind($this->connection);

            if (!$bind) {
                throw new \RuntimeException('Failed anonymous bind: ' . ldap_error($this->connection));
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
        $filter = str_replace('{user_dn}', $userDn, $this->groupSearchFilter);
        if ($username !== null) {
            $filter = str_replace('{username}', $username, $filter);
        }

        $this->logger->debug('LDAP: Searching groups', [
            'base_dn' => $baseDn,
            'filter' => $filter
        ]);

        $search = @ldap_search($this->connection, $baseDn, $filter, ['cn', 'memberOf', 'sAMAccountName']);

        if (!$search) {
            $this->logger->warning('LDAP: Group search error', [
                'error' => ldap_error($this->connection)
            ]);
            return $foundGroups;
        }

        $entries = ldap_get_entries($this->connection, $search);

        if ($entries['count'] === 0) {
            return $foundGroups;
        }

        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];

            if (isset($entry['cn'][0])) {
                $entryCn = $entry['cn'][0];
                if (!in_array($entryCn, $foundGroups, true)) {
                    $foundGroups[] = $entryCn;
                    $this->logger->debug('LDAP: Group found', ['group' => $entryCn]);
                }
            }

            if (isset($entry['memberof'])) {
                for ($j = 0; $j < $entry['memberof']['count']; $j++) {
                    $groupDn = $entry['memberof'][$j];

                    if (preg_match('/^CN=([^,]+)/', $groupDn, $matches)) {
                        $groupCn = $matches[1];

                        if (in_array($groupCn, $foundGroups, true)) {
                            continue;
                        }

                        $foundGroups[] = $groupCn;
                        $this->logger->debug('LDAP: Group found', ['group' => $groupCn]);

                        if ($this->groupSearchRecursive) {
                            $foundGroups = $this->searchGroupsRecursive($groupDn, $baseDn, null, $foundGroups);
                        }
                    }
                }
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
            $this->logger->debug('LDAP: Connection closed');
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
