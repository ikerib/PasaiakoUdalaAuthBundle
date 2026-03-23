<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LdapUser - Represents an LDAP authenticated user
 */
class LdapUser implements UserInterface
{
    private array $ldapAttributes = [];

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

    // Generic LDAP attribute access

    public function getLdapAttributes(): array
    {
        return $this->ldapAttributes;
    }

    public function setLdapAttributes(array $attributes): void
    {
        $this->ldapAttributes = $attributes;
    }

    public function getLdapAttribute(string $name): ?string
    {
        return $this->ldapAttributes[$name] ?? null;
    }

    public function setLdapAttribute(string $name, ?string $value): void
    {
        $this->ldapAttributes[$name] = $value;
    }

    // Convenience getters for common attributes (backwards compatible)

    public function getDepartment(): ?string
    {
        return $this->getLdapAttribute('department');
    }

    public function getDisplayName(): ?string
    {
        return $this->getLdapAttribute('displayName');
    }

    public function getExtensionName(): ?string
    {
        return $this->getLdapAttribute('extensionName');
    }

    public function getMail(): ?string
    {
        return $this->getLdapAttribute('mail');
    }

    public function getPreferredLanguage(): ?string
    {
        return $this->getLdapAttribute('preferredLanguage');
    }

    public function getDescription(): ?string
    {
        return $this->getLdapAttribute('description');
    }
}
