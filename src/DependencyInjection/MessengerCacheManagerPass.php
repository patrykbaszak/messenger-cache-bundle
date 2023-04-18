<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\DependencyInjection;

use PBaszak\MessengerCacheBundle\Decorator\MessageBusCacheDecorator;
use PBaszak\MessengerCacheBundle\Decorator\MessageBusCacheEventsDecorator;
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
        $manager->setArgument('$refreshTriggeredTtl', $container->getParameter('messenger_cache.refresh_triggered_ttl'));
        $useEvents = $container->getParameter('messenger_cache.use_events');

        /** @var string[] $decoratedBuses */
        $decoratedBuses = array_unique($container->getParameter('messenger_cache.decorated_message_buses'));
        /** @var string[] $messageBusDecorators */
        $messageBusDecorators = $container->getParameter('messenger_cache.message_bus_decorators');

        if ($useEvents) {
            $messageBusDecorators = array_filter($messageBusDecorators, function ($decorator) {
                return MessageBusCacheDecorator::class !== $decorator && MessageBusCacheEventsDecorator::class !== $decorator;
            });

            $messageBusDecorators['0'] = MessageBusCacheEventsDecorator::class;
            $messageBusDecorators['1'] = MessageBusCacheDecorator::class;
        }

        uksort($messageBusDecorators, function ($a, $b) {
            $a = (int) str_replace('_', '-', (string) $a);
            $b = (int) str_replace('_', '-', (string) $b);

            return $b <=> $a;
        });

        foreach ($decoratedBuses as $bus) {
            $decoratedDefinitionId = $bus.'.decorated';
            $definition = $container->getDefinition($bus);
            $container->setDefinition($decoratedDefinitionId, $definition);
            $i = 0;
            foreach ($messageBusDecorators as $priority => $decorator) {
                $id = $bus.'.decorator_no_'.$i;
                $decorator = $container->register($id, $decorator)
                    ->setArgument('$decorated', $earlierDecorator ?? $definition)
                    ->setAutowired(true)
                    ->setAutoConfigured(true);
                ++$i;
                $earlierDecorator = $decorator;
            }

            $container->setDefinition($bus, $decorator ?? $definition);
        }
    }
}
