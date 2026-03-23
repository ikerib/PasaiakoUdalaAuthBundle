<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after LDAP groups are loaded and mapped to roles.
 * Listeners can modify the roles array before the user object is created.
 */
class LdapGroupsLoadedEvent extends Event
{
    public function __construct(
        private readonly string $username,
        private readonly array $groups,
        private array $roles,
        private readonly string $authMethod,
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getAuthMethod(): string
    {
        return $this->authMethod;
    }
}
