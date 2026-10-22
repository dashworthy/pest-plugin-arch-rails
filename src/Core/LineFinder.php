<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Core;

use Pest\Arch\Support\FileLineFinder;

/**
 * Line finders that point a violation at a useful line.
 */
final class LineFinder
{
    /**
     * The first line containing the needle, falling back to the line declaring the class.
     *
     * FileLineFinder::where() returns 0 when nothing in the file matches, which points a violation at line 0. This
     * tries the needle (usually a method signature) first and, when the method is missing, falls back to the class.
     *
     * @return callable(string): int
     */
    public static function methodOrClass(string $needle): callable
    {
        return static function (string $path) use ($needle): int {
            $line = FileLineFinder::where(fn (string $candidate): bool => str_contains($candidate, $needle))($path);

            return $line !== 0
                ? $line
                : FileLineFinder::where(fn (string $candidate): bool => str_contains($candidate, 'class'))($path);
        };
    }
}
