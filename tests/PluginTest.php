<?php

namespace tests;

use Chiron\PackageDiscovery\Plugin;

class PluginTest extends TestCase
{
    public function testDiscoverPackages()
    {
        $outputFilepath = __DIR__ . '/fixtures/packages.php';
        @unlink($outputFilepath);

        $this->invokeMethod(new Plugin(), 'discoverPackages', [__DIR__ . '/fixtures/vendor', $outputFilepath]);

        $manifest = include $outputFilepath;

        // create a list with all the prodivers found.
        foreach ($manifest as $key => $value) {
            $providers = $value['providers'];

            if (is_array($providers)) {
                $result = array_merge($result, $providers);
            } else {
                $result[] = $providers;
            }
        }

        $this->assertEquals(['foo', 'bar', 'baz'], $result);

        unlink($outputFilepath);
    }
}
