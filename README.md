[![Build Status](https://travis-ci.org/ncou/Chiron-PackageDiscovery.svg?branch=master)](https://travis-ci.org/ncou/Chiron-PackageDiscovery)
[![Coverage Status](https://coveralls.io/repos/github/ncou/Chiron-PackageDiscovery/badge.svg?branch=master)](https://coveralls.io/github/ncou/Chiron-PackageDiscovery?branch=master)
[![CodeCov](https://codecov.io/gh/ncou/Chiron-PackageDiscovery/branch/master/graph/badge.svg)](https://codecov.io/gh/ncou/Chiron-PackageDiscovery)

[![Latest Stable Version](https://poser.pugx.org/chiron/package-discovery/v/stable.png)](https://packagist.org/packages/chiron/package-discovery)
[![Total Downloads](https://img.shields.io/packagist/dt/chiron/package-discovery.svg?style=flat-square)](https://packagist.org/packages/chiron/package-discovery/stats)
[![Monthly Downloads](https://img.shields.io/packagist/dm/chiron/package-discovery.svg?style=flat-square)](https://packagist.org/packages/chiron/package-discovery/stats)

[![StyleCI](https://styleci.io/repos/150878536/shield?style=flat)](https://styleci.io/repos/150878536)
[![PHP-Eye](https://php-eye.com/badge/chiron/package-discovery/tested.svg?style=flat)](https://php-eye.com/package/chiron/package-discovery)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

Chiron Package Discovery
---------------

Basic Composer script to allow package discovery for the Chiron micro framework.

Installation
------------

    $ composer require chiron/package-discovery

Usage
-----

The `ComposerScripts` class also implements a static method `postCreateProject()` that can be called after
a Chiron project is created, through the `post-create-project-cmd` composer script.
A similar method exists for running tasks after each `composer install` call, which is `postInstall()`.
These methods allow to run other `ComposerScripts` class methods like `setPermission()` or `generateApplicationKey()`, 
depending on the corresponding parameters set in the `extra` section of the `composer.json` file.
For example,

```json
{
    "name": "chiron/skeleton",
    "type": "project",
    ...
    "extra": {
        "post-create-project-cmd": {
            "copyFiles": [
                {
                    "config/templates/console-local.php": "config/console-local.php",
                    "config/templates/web-local.php": "config/web-local.php",
                    "config/templates/db-local.php": "config/db-local.php",
                    "config/templates/cache.json": ["runtime/cache.json", true]
                }
            ],
            "generateApplicationKey": [
                "config/web-local.php"
            ]
        },
        "post-install-cmd": {
            "setPermission": [
                {
                    "runtime": "0777",
                    "web/assets": "0777",
                    "chiron": "0755"
                }
            ]
        }
    }
}
```
