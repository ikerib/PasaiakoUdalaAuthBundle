<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LdapUser - Represents an LDAP authenticated user
 */
class LdapUser implements UserInterface
{
    private string $username;
    private array $roles;
    private array $groups;
    private array $certificateData;
    private array $ldapAttributes = [];

    public function __construct(
        string $username,
        array $roles,
        array $groups = [],
        array $certificateData = []
    ) {
        $this->username = $username;
        $this->roles = $roles;
        $this->groups = $groups;
        $this->certificateData = $certificateData;
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

    public function __serialize(): array
    {
        return [
            'username' => $this->username,
            'roles' => $this->roles,
            'groups' => $this->groups,
            'certificateData' => $this->certificateData,
            'ldapAttributes' => $this->ldapAttributes,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->username = (string) ($data['username'] ?? '');
        $this->roles = (array) ($data['roles'] ?? []);
        $this->groups = (array) ($data['groups'] ?? []);
        $this->certificateData = (array) ($data['certificateData'] ?? []);
        $this->ldapAttributes = (array) ($data['ldapAttributes'] ?? []);
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
