<?php

namespace Composer\Autoload;

/**
 * Minimal Composer\Autoload\ClassLoader shim.
 *
 * The real class ships inside composer's own vendor/autoload.php, which this
 * sandbox replaces with a lightweight autoloader (scripts/fetch-vendor.mjs).
 * Laravel's exception renderer and some dev tools call
 * ClassLoader::getRegisteredLoaders() / ->getClassMap(), so expose our
 * generated maps through the same public API.
 */
class ClassLoader
{
    private static array $registeredLoaders = [];

    private array $classMap = [];

    private array $prefixesPsr4 = [];

    public static function getRegisteredLoaders(): array
    {
        return self::$registeredLoaders;
    }

    public function register(bool $prepend = false): static
    {
        self::$registeredLoaders[__DIR__] = $this;

        return $this;
    }

    public function getClassMap(): array
    {
        return $this->classMap;
    }

    public function addClassMap(array $classMap): static
    {
        $this->classMap = array_merge($this->classMap, $classMap);

        return $this;
    }

    public function getPrefixesPsr4(): array
    {
        return $this->prefixesPsr4;
    }

    public function setPsr4(string $prefix, array $paths): static
    {
        $this->prefixesPsr4[$prefix] = $paths;

        return $this;
    }
}
