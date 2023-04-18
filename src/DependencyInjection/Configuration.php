<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\DependencyInjection;

use PBaszak\MessengerCacheBundle\MessengerCacheBundle;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(MessengerCacheBundle::ALIAS);

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('refresh_triggered_ttl')
                    ->defaultValue(600)
                ->end()
                ->arrayNode('message_bus_decorators')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('alias')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('pools')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('alias')
                    ->prototype('scalar')->end()
                    ->children()
                        ->scalarNode('default')->defaultValue(AdapterInterface::class)->end()
                    ->end()
                ->end()
                ->arrayNode('decorated_message_buses')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->defaultValue(['cachedMessage.bus'])
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
