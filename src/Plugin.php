<?php

declare(strict_types = 1);

namespace Zend\ComponentInstaller;

use ArrayObject;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use DirectoryIterator;
use Zend\ComponentInstaller\Injector\AbstractInjector;
use Zend\ComponentInstaller\Injector\ConfigInjectorChain;
use Zend\ComponentInstaller\Injector\InjectorInterface;
use Composer\Script\ScriptEvents;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Util\Filesystem;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var string
     */
    // TODO : à renommer en discovered.json ???
    private const DISCOVERY_MANIFEST_FILENAME = '/composer/discovery.json';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var JsonFile
     */
    private $manifest;

    /**
     * Activate plugin.
     *
     * Sets internal pointers to Composer and IOInterface instances.
     *
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer        = $composer;
        $this->io              = $io;
        $this->manifest        = $this->initManifestFile();
    }

    /**
     * Initialise an empty json manifest file if it doesn't already exists.
     * Write in the console + stderr when the file can't be created/read/write.
     *
     * @return JsonFile
     */
    private function initManifestFile(): JsonFile
    {
        $fs = new Filesystem();

        $vendorDir    = rtrim($this->composer->getConfig()->get('vendor-dir'), '/');
        $manifestFile = $fs->normalizePath($vendorDir . self::DISCOVERY_MANIFEST_FILENAME);

        $newlyCreated = !file_exists($manifestFile);

        if ($newlyCreated && !file_put_contents($manifestFile, "{\n}\n")) {
            $this->io->writeError('<error>'.$manifestFile.' could not be created.</error>');
            exit(1);
        }
        if (!is_readable($manifestFile)) {
            $this->io->writeError('<error>'.$manifestFile.' is not readable.</error>');
            exit(1);
        }
        if (!is_writable($manifestFile)) {
            $this->io->writeError('<error>'.$manifestFile.' is not writable.</error>');
            exit(1);
        }
        if (filesize($manifestFile) === 0) {
            file_put_contents($manifestFile, "{\n}\n");
        }

        return new JsonFile($manifestFile);
    }

    /**
     * Return list of event handlers in this class.
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            ScriptEvents::POST_PACKAGE_UNINSTALL => 'onPostPackageUninstall',
        ];
    }

    /**
     * post-package-install event hook.
     *
     * This routine will attempt to update the manifest json file (discovery.json)
     * using the value(s) discovered in extra.chiron writing their values into the manifest.
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPostPackageInstall(PackageEvent $event): void
    {
        $package = $event->getOperation()->getPackage();
        $name    = $package->getName();
        $extra   = $this->getExtraMetadata($package->getExtra());

        // TODO : ajouter une vérifaction si dans les extra les valeurs sont correctes cad une string non vide et égale à "service-provider", et soit un tableau soit une chaine. Si ce n'est pas le cas on retire l'item du tableau $extra. on ne doit pas lever d'exception !!!!!! eventuellement lever un $io->write(erreur de format) si on est en mode verbeux.

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        $this->injectPackageIntoManifest($name, $extra);
    }

    /**
     * Inject an invidual package into available configuration.
     *
     * @param string $package Package name
     * @param string $module Module to install in configuration
     * @return void
     */
    // TODO : vérifier le typehint de $module c'est plutot un tableau qu'une chaine :(
    private function injectPackageIntoManifest(string $package, $module): void
    {
        $this->io->write(sprintf('<info>    Installing service providers for the package %s</info>', $package));

        $manipulator = new JsonManipulator(file_get_contents($this->manifest->getPath()));
        $manipulator->addMainKey($package, $module);
        file_put_contents($this->manifest->getPath(), $manipulator->getContents());
    }

    /**
     * post-package-uninstall event hook
     *
     * This routine will attempt to update the manifest json file (discovery.json)
     * removing their values from the manifest file.
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPostPackageUninstall(PackageEvent $event): void
    {
        $package = $event->getOperation()->getPackage();
        $name    = $package->getName();

        $this->removePackageFromManifest($name);
    }

    /**
     * Remove an individual package defined in a package from configuration.
     *
     * @param string $package Package in which module is defined
     * @return void
     */
    private function removePackageFromManifest(string $package): void
    {
        $this->io->write(sprintf('<info>    Removing service providers for the package %s</info>', $package));

        $manipulator = new JsonManipulator(file_get_contents($this->manifest->getPath()));
        $manipulator->removeMainKey($package);
        file_put_contents($this->manifest->getPath(), $manipulator->getContents());
    }

    /**
     * Retrieve the chiron-specific metadata from the "extra" section
     *
     * @param array $extra
     * @return array
     */
    private function getExtraMetadata(array $extra): array
    {
        return isset($extra['chiron']) && is_array($extra['chiron'])
            ? $extra['chiron']
            : []
        ;
    }

/*
    protected function showDepsTree()
    {
        if (!$this->io->isVerbose()) {
            return;
        }
        foreach (array_reverse($this->orderedList) as $name => $depth) {
            $deps = $this->originalFiles[$name];
            $color = $this->colors[$depth % count($this->colors)];
            $indent = str_repeat('   ', $depth - 1);
            $package = $this->plainList[$name];
            $showdeps = $deps ? '<comment>[' . implode(',', array_keys($deps)) . ']</>' : '';
            $this->io->write(sprintf('%s - <fg=%s;options=bold>%s</> %s %s', $indent, $color, $name, $package->getFullPrettyVersion(), $showdeps));
        }
    }
*/

}
