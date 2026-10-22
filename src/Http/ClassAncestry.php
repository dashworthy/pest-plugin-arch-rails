<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Http;

use Composer\Autoload\ClassLoader;
use Dashworthy\PestPluginArchRails\Core\MethodSource;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;

/**
 * Answers whether one class extends another, without requiring the ancestor to be installed.
 *
 * A class that loads is answered by reflection. One that cannot load, because a framework class it extends is not
 * installed, is answered by reading `extends` from source files located through Composer, one parent at a time.
 */
final class ClassAncestry
{
    private const int MAX_DEPTH = 32;

    public static function extends(string $class, string $ancestor): bool
    {
        $class = ltrim($class, '\\');
        $ancestor = ltrim($ancestor, '\\');

        for ($depth = 0; $depth < self::MAX_DEPTH; $depth++) {
            if (strcasecmp($class, $ancestor) === 0) {
                return true;
            }

            if (class_exists($ancestor, false) && self::loads($class)) {
                return is_a($class, $ancestor, true);
            }

            $parent = self::parentFromSource($class);

            if ($parent === null) {
                return false;
            }

            $class = $parent;
        }

        return false;
    }

    private static function loads(string $class): bool
    {
        try {
            return class_exists($class);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function parentFromSource(string $class): ?string
    {
        $file = self::fileOf($class);

        if ($file === null) {
            return null;
        }

        $code = @file_get_contents($file);

        if ($code === false) {
            return null;
        }

        $short = strtolower(substr($class, (int) strrpos('\\'.$class, '\\')));

        /** @var list<Class_> $classes */
        $classes = (new NodeFinder)->findInstanceOf(MethodSource::parseCode($code), Class_::class);

        foreach ($classes as $node) {
            if ($node->name?->toLowerString() === $short) {
                return $node->extends?->toString();
            }
        }

        return null;
    }

    private static function fileOf(string $class): ?string
    {
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $file = $loader->findFile($class);

            if (is_string($file)) {
                return $file;
            }
        }

        return null;
    }
}
