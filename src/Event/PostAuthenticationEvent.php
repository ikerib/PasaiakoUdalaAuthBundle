<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Event;

use PasaiaUdala\AuthBundle\Security\LdapUser;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a successful authentication (LDAP or Certificate).
 * Listeners can use this to perform post-login actions (audit log, sync, etc.).
 */
class PostAuthenticationEvent extends Event
{
    public function __construct(
        private readonly LdapUser $user,
        private readonly string $authMethod,
    ) {
    }

    public function getUser(): LdapUser
    {
        return $this->user;
    }

    /**
     * Returns 'ldap' or 'certificate'.
     */
    public function getAuthMethod(): string
    {
        return $this->authMethod;
    }
}
