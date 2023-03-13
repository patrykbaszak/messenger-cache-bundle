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
        $pools = $container->getParameter('messenger_cache.pools');
        $manager = $container->getDefinition('messenger_cache.manager');

        $poolDefinitions = [];
        foreach ($pools as $alias => $pool) {
            $poolDefinitions[$alias] = $container->findDefinition($pool);
        }

        $manager->setArgument('$pools', $poolDefinitions);
        $manager->setArgument('$kernelCacheDir', $container->getParameter('kernel.cache_dir'));
    }
}
