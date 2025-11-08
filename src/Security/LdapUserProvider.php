<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Security;

use PasaiaUdala\AuthBundle\Service\LdapClient;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * LdapUserProvider - Loads users from LDAP
 */
class LdapUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly LdapClient $ldapClient
    ) {
    }

    /**
     * Load user by username (called after successful authentication)
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Get user's groups from LDAP
        $groups = $this->ldapClient->getUserGroups($identifier);

        // Map groups to roles
        $roles = $this->ldapClient->mapGroupsToRoles($groups);

        return new LdapUser($identifier, $roles, $groups);
    }

    /**
     * Refresh user (reload from LDAP)
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof LdapUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * Check if this provider supports the given user class
     */
    public function supportsClass(string $class): bool
    {
        return LdapUser::class === $class || is_subclass_of($class, LdapUser::class);
    }

    /**
     * Legacy method for Symfony < 6
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }
}
