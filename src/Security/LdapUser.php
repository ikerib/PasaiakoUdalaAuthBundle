<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LdapUser - Represents an LDAP authenticated user
 */
class LdapUser implements UserInterface
{
    public function __construct(
        private readonly string $username,
        private readonly array $roles,
        private readonly array $groups = [],
        private readonly array $certificateData = []
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getCertificateData(): array
    {
        return $this->certificateData;
    }

    public function isCertificateAuthenticated(): bool
    {
        return !empty($this->certificateData);
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase (no password stored)
    }
}
