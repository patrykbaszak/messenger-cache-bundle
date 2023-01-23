<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const BUNDLES = [
        \Symfony\Bundle\FrameworkBundle\FrameworkBundle::class,
        \PBaszak\MessengerCacheBundle\MessengerCacheBundle::class,
    ];

    public function registerBundles(): iterable
    {
        foreach (self::BUNDLES as $bundle) {
            yield new $bundle();
        }
    }
}
