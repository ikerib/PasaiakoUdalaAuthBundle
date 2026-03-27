<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * LdapAuthExtension - Loads and manages bundle configuration
 */
class PasaiaUdalaAuthExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        // Register Twig template path so @PasaiakoUdalaAuth namespace works
        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    __DIR__ . '/../Resources/views' => 'PasaiakoUdalaAuth',
                ],
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Always register Twig globals so templates work regardless of LDAP configuration
        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'globals' => [
                    'pasaiako_udala_auth_base_template' => $config['base_template'],
                    'pasaiako_udala_auth_routes' => $config['routes'],
                ],
            ]);
        }

        // If no LDAP host is configured, do not register services to avoid
        // breaking the host application when the bundle is installed but not configured.
        $serverConfig = $config['server'] ?? [];

        if (empty($serverConfig['host'])) {
            // Mark the bundle as disabled via a parameter so services can adapt if necessary
            $container->setParameter('pasaiako_udala_auth.enabled', false);
            return;
        }

        // Store configuration as parameters
        $container->setParameter('pasaiako_udala_auth.enabled', true);
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
        $container->setParameter('pasaiako_udala_auth.dni_field', $config['dni_field']);
        $container->setParameter('pasaiako_udala_auth.user_attributes', $config['user_attributes']);
        $container->setParameter('pasaiako_udala_auth.cache_ttl', $config['cache_ttl']);
        $container->setParameter('pasaiako_udala_auth.base_template', $config['base_template']);
        $container->setParameter('pasaiako_udala_auth.routes.home', $config['routes']['home']);
        $container->setParameter('pasaiako_udala_auth.routes.login_selector', $config['routes']['login_selector']);
        $container->setParameter('pasaiako_udala_auth.routes.login_ldap', $config['routes']['login_ldap']);
        $container->setParameter('pasaiako_udala_auth.routes.oauth_connect', $config['routes']['oauth_connect']);
        $container->setParameter('pasaiako_udala_auth.routes.oauth_check', $config['routes']['oauth_check']);

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
