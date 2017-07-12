<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ibtikar_share_economy_dashboard_design');

        $rootNode
                ->children()
                ->arrayNode('navBarMenuBundles')
                ->prototype('scalar')->end()
                ->end()
                ->scalarNode('dashboard_list_autocompelete')
                ->defaultFalse()
                ->end()
                ->scalarNode('dashboard_list_autocompeleteMinNoOfCharacter')
                ->defaultValue(2)
                ->end()
                ->end()
        ;

        return $treeBuilder;
    }
}
