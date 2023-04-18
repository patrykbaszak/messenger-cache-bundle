<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MessengerCacheExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $container->setParameter('messenger_cache.pools', $config['pools']);
        $container->setParameter('messenger_cache.use_events', $config['use_events']);
        $container->setParameter('messenger_cache.decorated_message_buses', $config['decorated_message_buses']);
        $container->setParameter('messenger_cache.refresh_triggered_ttl', $config['refresh_triggered_ttl']);
        $container->setParameter('messenger_cache.message_bus_decorators', $config['message_bus_decorators']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('packages/messenger.yaml');
        $loader->load('packages/messenger_cache.yaml');
    }
}
