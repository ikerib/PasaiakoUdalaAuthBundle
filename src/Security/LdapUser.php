<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LdapUser - Represents an LDAP authenticated user
 */
class LdapUser implements UserInterface
{
    private ?string $department = null;
    private ?string $displayName = null;
    private ?string $extensionName = null;
    private ?string $mail = null;
    private ?string $preferredLanguage = null;
    private ?string $description = null;

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

    // LDAP attribute getters and setters

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): void
    {
        $this->department = $department;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }

    public function setExtensionName(?string $extensionName): void
    {
        $this->extensionName = $extensionName;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): void
    {
        $this->mail = $mail;
    }

    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(?string $preferredLanguage): void
    {
        $this->preferredLanguage = $preferredLanguage;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
