<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle;

use PasaiaUdala\AuthBundle\DependencyInjection\PasaiaUdalaAuthExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * PasaiaUdalaAuthBundle - Authentication Bundle for Pasaia Udala
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
class PasaiaUdalaAuthBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new PasaiaUdalaAuthExtension();
    }
}
