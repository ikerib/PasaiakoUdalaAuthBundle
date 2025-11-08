<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle;

use PasaiaUdala\AuthBundle\DependencyInjection\PasaiakoUdalaAuthExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * PasaiakoUdalaAuthBundle - Authentication Bundle for Pasaia Udala
 *
 * Provides dual authentication system (LDAP + Izenpe Certificate) with group search and role mapping.
 *
 * Features:
 * - LDAP server authentication (Active Directory)
 * - Certificate authentication (Izenpe OAuth2)
 * - Recursive group membership search
 * - Flexible group-to-role mapping via configuration
 * - Compatible with Symfony Security component
 *
 * @author Pasaia Udala
 * @version 1.1.0
 */
class PasaiakoUdalaAuthBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return __DIR__;
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new PasaiakoUdalaAuthExtension();
    }
}
