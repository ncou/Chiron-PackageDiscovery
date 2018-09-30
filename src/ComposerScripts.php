<?php

namespace Chiron\PackageDiscovery;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

// TODO : générer la clé aléatoire du APP_KEY dans le fichier .env
//https://github.com/laravel/framework/blob/248245ab3e9d2876e0331df318410bc21a5bca01/src/Illuminate/Foundation/Console/KeyGenerateCommand.php

class ComposerScripts implements
    EventSubscriberInterface,
    PluginInterface
{

    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * Activate plugin.
     *
     * Sets internal pointers to Composer and IOInterface instances, and resets
     * cached injector map.
     *
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer        = $composer;
        $this->io              = $io;
    }
    /**
     * Return list of event handlers in this class.
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump'   => 'postAutoloadDump2',
        ];
        /*
        return [
            'post-package-install'   => 'onPostPackageInstall',
            'post-package-uninstall' => 'onPostPackageUninstall',
        ];*/
    }

    public static function postAutoloadDump2(PackageEvent $event): void
    {
        throw new Exception("TEST_NCOU");
    }

    /**
     * Build the manifest (packages discovered) and write it to disk.
     *
     * @return void
     */
    public static function postAutoloadDump(Event $event): void
    {
        $vendorPath = $event->getComposer()->getConfig()->get('vendor-dir');

        require_once $vendorPath . '/autoload.php';

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

        static::writeManifest($vendorPath . '/../bootstrap/cache/packages.php', $discoverPackages);
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
    private static function writeManifest(string $manifestPath, array $manifest):void
    {
        if (! is_writable(dirname($manifestPath))) {
            throw new \RuntimeException('The directory "'.dirname($manifestPath).'" must be present and writable.');
        }

        file_put_contents($manifestPath, '<?php return ' . var_export($manifest, true) . ';');
    }

    /**
     * Special method to run tasks defined in `[extra][yii\composer\Installer::postCreateProject]` key in `composer.json`
     *
     * @param Event $event
     */
    public static function postCreateProject(Event $event)
    {
        static::runCommands($event, __METHOD__);
    }
    /**
     * Special method to run tasks defined in `[extra][yii\composer\Installer::postInstall]` key in `composer.json`
     *
     * @param Event $event
     * @since 2.0.5
     */
    public static function postInstall(Event $event)
    {
        static::runCommands($event, __METHOD__);
    }
    /**
     * Special method to run tasks defined in `[extra][$extraKey]` key in `composer.json`
     *
     * @param Event $event
     * @param string $extraKey
     */
    private static function runCommands(Event $event, string $extraKey): void
    {
        $params = $event->getComposer()->getPackage()->getExtra();
        if (isset($params[$extraKey]) && is_array($params[$extraKey])) {
            foreach ($params[$extraKey] as $method => $args) {
                call_user_func_array([__CLASS__, $method], (array) $args);
            }
        }
    }
    /**
     * Sets the correct permission for the files and directories listed in the extra section.
     * @param array $paths the paths (keys) and the corresponding permission octal strings (values)
     */
    private static function setPermission(array $paths): void
    {
        foreach ($paths as $path => $permission) {
            echo "chmod('$path', $permission)...";
            if (is_dir($path) || is_file($path)) {
                try {
                    if (chmod($path, octdec($permission))) {
                        echo "done.\n";
                    };
                } catch (\Exception $e) {
                    echo $e->getMessage() . "\n";
                }
            } else {
                echo "file not found.\n";
            }
        }
    }
    /**
     * Generates a cookie validation key for every app config listed in "config" in extra section.
     * You can provide one or multiple parameters as the configuration files which need to have validation key inserted.
     */
    private static function generateCookieValidationKey(): void
    {
        $configs = func_get_args();
        $key = self::generateRandomString();
        foreach ($configs as $config) {
            if (is_file($config)) {
                $content = preg_replace('/(("|\')cookieValidationKey("|\')\s*=>\s*)(""|\'\')/', "\\1'$key'", file_get_contents($config), -1, $count);
                if ($count > 0) {
                    file_put_contents($config, $content);
                }
            }
        }
    }
    private static function generateRandomString(): string
    {
        $length = 32;
        $bytes = random_bytes($length);
        return strtr(substr(base64_encode($bytes), 0, $length), '+/=', '_-.');
    }
    /**
     * Copy files to specified locations.
     * @param array $paths The source files paths (keys) and the corresponding target locations
     * for copied files (values). Location can be specified as an array - first element is target
     * location, second defines whether file can be overwritten (by default method don't overwrite
     * existing files).
     */
    private static function copyFiles(array $paths): void
    {
        foreach ($paths as $source => $target) {
            // handle file target as array [path, overwrite]
            $target = (array) $target;
            echo "Copying file $source to $target[0] - ";
            if (!is_file($source)) {
                echo "source file not found.\n";
                continue;
            }
            if (is_file($target[0]) && empty($target[1])) {
                echo "target file exists - skip.\n";
                continue;
            } elseif (is_file($target[0]) && !empty($target[1])) {
                echo "target file exists - overwrite - ";
            }
            try {
                if (!is_dir(dirname($target[0]))) {
                    mkdir(dirname($target[0]), 0777, true);
                }
                if (copy($source, $target[0])) {
                    echo "done.\n";
                }
            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }

}
