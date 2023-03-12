<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MessengerCacheManagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var array<string,string> */
        $adapters = $container->getParameter('messenger_cache.adapters');
        $manager = $container->getDefinition('messenger_cache.manager');

        if ($container->getParameter('messenger_cache.runtime_cache_storage')) {
            $runtimeCacheStorageDecorator = $container->getDefinition('messenger_cache.manager.runtime_cache_storage_decorator');
            $runtimeCacheStorageDecorator->setDecoratedService('messenger_cache.manager');
            $runtimeCacheStorageDecorator->setArgument('$decorated', $manager);
        }

        $adapterDefinitions = [];
        foreach ($adapters as $alias => $adapter) {
            $adapterDefinitions[$alias] = $container->findDefinition($adapter);
        }

        $manager->setArgument('$adapters', $adapterDefinitions);
        $manager->setArgument('$kernelCacheDir', $container->getParameter('kernel.cache_dir'));
    }
}
