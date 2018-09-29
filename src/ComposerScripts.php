<?php

namespace Chiron\PackageDiscovery;

use Composer\Script\Event;

class ComposerScripts
{
    /**
     * Build the manifest and write it to disk.
     *
     * @return void
     */
    public static function postDump(Event $event): void
    {
        $vendorPath = $event->getComposer()->getConfig()->get('vendor-dir');

        //require_once $vendorPath . '/autoload.php';

        $installedPackages = [];
        if (file_exists($path = $vendorPath . '/composer/installed.json')) {
            $installedPackages = json_decode(file_get_contents($path), true);
        }

        $discoverPackages = [];
        foreach ($installedPackages as $package) {
            if (!empty($package['extra']['chiron'])) {
                $packageInfo = $package['extra']['chiron'];
                $discoverPackages[$package['name']] = $packageInfo;
            }
        }

        static::write($vendorPath . '/../bootstrap/cache/packages.php', $discoverPackages);
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  string  $manifestPath
     * @param  array  $manifest
     * @return void
     *
     * @throws \RuntimeException
     */
    private static function write(string $manifestPath, array $manifest):void
    {
        if (! is_writable(dirname($manifestPath))) {
            throw new \RuntimeException('The directory "'.dirname($manifestPath).'" must be present and writable.');
        }

        file_put_contents($manifestPath, '<?php return ' . var_export($manifest, true) . ';');
    }
}
