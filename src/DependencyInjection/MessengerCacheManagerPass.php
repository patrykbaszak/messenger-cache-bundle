<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\DependencyInjection;

use PBaszak\MessengerCacheBundle\Decorator\MessageBusCacheDecorator;
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

        /** @var string[] $decoratedBuses */
        $decoratedBuses = $container->getParameter('messenger_cache.decorated_message_buses');

        /* feat/7 - messageBus decoration strategy implementation */
        foreach ($decoratedBuses as $bus) {
            $definition = $container->getDefinition($bus);
            $decorator = $container->register($bus.'.cache_decorator', MessageBusCacheDecorator::class)
                ->setArgument('$decorated', $definition)
                ->setAutowired(true)
                ->setAutoConfigured(true);
            $decorator->setDecoratedService($bus);
        }
    }
}
