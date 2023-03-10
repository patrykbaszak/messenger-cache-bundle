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
                ->arrayNode('adapters')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('alias')
                    ->prototype('scalar')->end()
                    ->children()
                        ->scalarNode('default')->defaultValue(AdapterInterface::class)->isRequired()->end()
                    ->end()
                ->end()
                ->booleanNode('runtime_cache_storage')->defaultFalse()->end()
                ->booleanNode('cacheable_callback_support')->defaultTrue()->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
