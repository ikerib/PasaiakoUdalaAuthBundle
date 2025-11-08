<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * LdapAuthExtension - Loads and manages bundle configuration
 */
class PasaiakoUdalaAuthExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Store configuration as parameters
        $container->setParameter('pasaiako_udala_auth.server.host', $config['server']['host']);
        $container->setParameter('pasaiako_udala_auth.server.port', $config['server']['port']);
        $container->setParameter('pasaiako_udala_auth.server.encryption', $config['server']['encryption']);
        $container->setParameter('pasaiako_udala_auth.server.base_dn', $config['server']['base_dn']);
        $container->setParameter('pasaiako_udala_auth.server.user_dn_pattern', $config['server']['user_dn_pattern']);
        $container->setParameter('pasaiako_udala_auth.server.bind_dn', $config['server']['bind_dn'] ?? null);
        $container->setParameter('pasaiako_udala_auth.server.bind_password', $config['server']['bind_password'] ?? null);
        $container->setParameter('pasaiako_udala_auth.role_mapping', $config['role_mapping']);
        $container->setParameter('pasaiako_udala_auth.default_role', $config['default_role']);
        $container->setParameter('pasaiako_udala_auth.group_search.enabled', $config['group_search']['enabled']);
        $container->setParameter('pasaiako_udala_auth.group_search.base_dn', $config['group_search']['base_dn']);
        $container->setParameter('pasaiako_udala_auth.group_search.filter', $config['group_search']['filter']);
        $container->setParameter('pasaiako_udala_auth.group_search.recursive', $config['group_search']['recursive']);

        // Load services
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'pasaiako_udala_auth';
    }
}
