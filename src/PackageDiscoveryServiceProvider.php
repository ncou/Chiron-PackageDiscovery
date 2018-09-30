<?php

declare(strict_types=1);

namespace Chiron\PackageDiscovery;

use Psr\Container\ContainerInterface;

class PackageDiscoveryServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(ContainerInterface $container)
    {
        $packages = [];
        $packagesFile = base_path() . '/bootstrap/cache/packages.php';

        if (file_exists($packagesFile)) {
            $packages = include $packagesFile;
        }

        foreach ($packages as $package) {
            if (! empty($package['providers'])) {
                array_walk($package['providers'], function ($provider) use ($container) {
                    $container->register($provider);
                });
            }
        }
    }
}
