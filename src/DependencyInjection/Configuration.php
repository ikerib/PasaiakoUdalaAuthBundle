<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration - Defines the bundle configuration structure
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pasaiako_udala_auth');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('server')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')
                            ->defaultNull()
                            ->info('LDAP server hostname (required for enabling the bundle)')
                        ->end()
                        ->integerNode('port')
                            ->defaultValue(389)
                            ->info('LDAP server port (389 for LDAP, 636 for LDAPS)')
                        ->end()
                        ->enumNode('encryption')
                            ->values(['none', 'ssl', 'tls'])
                            ->defaultValue('none')
                            ->info('Encryption type: none, ssl (ldaps://), or tls (STARTTLS)')
                        ->end()
                        ->scalarNode('base_dn')
                            ->defaultNull()
                            ->info('Base DN for searches (e.g., dc=pasaia,dc=eus)')
                        ->end()
                        ->scalarNode('user_dn_pattern')
                            ->defaultNull()
                            ->info('DN pattern for user authentication. Use {username} placeholder (e.g., uid={username},ou=users,dc=pasaia,dc=eus)')
                        ->end()
                        ->scalarNode('bind_dn')
                            ->defaultNull()
                            ->info('Optional: DN for bind authentication (if server requires bind before search)')
                        ->end()
                        ->scalarNode('bind_password')
                            ->defaultNull()
                            ->info('Optional: Password for bind DN')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('role_mapping')
                    ->info('Map LDAP groups to Symfony roles')
                    ->useAttributeAsKey('group')
                    ->scalarPrototype()->end()
                    ->example([
                        'informatika' => 'ROLE_ADMIN',
                        'GGBB' => 'ROLE_KUDEATU',
                        'langilea' => 'ROLE_USER'
                    ])
                ->end()
                ->scalarNode('default_role')
                    ->defaultValue('ROLE_USER')
                    ->info('Default role for authenticated users without group mapping')
                ->end()
                ->arrayNode('group_search')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Enable group membership search')
                        ->end()
                        ->scalarNode('base_dn')
                            ->defaultNull()
                            ->info('Base DN for group search (defaults to server base_dn)')
                        ->end()
                        ->scalarNode('filter')
                            ->defaultValue('(member={user_dn})')
                            ->info('LDAP filter for group search. Use {user_dn} placeholder')
                        ->end()
                        ->booleanNode('recursive')
                            ->defaultTrue()
                            ->info('Search groups recursively (nested groups)')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
