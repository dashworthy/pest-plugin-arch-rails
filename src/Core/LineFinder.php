<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Core;

use Pest\Arch\Support\FileLineFinder;

/**
 * Line finders that point a violation at a useful line.
 */
final class LineFinder
{
    /** A line declaring a class, interface, trait or enum, not merely mentioning one. */
    private const string DECLARATION = '/^\s*(#\[.*\]\s*)*((final|abstract|readonly)\s+)*(class|interface|trait|enum)\s/';

    /**
     * The line declaring the class, interface, trait or enum.
     *
     * FileLineFinder::where() returns 0 when nothing in the file matches, which points a violation at line 0. This
     * tries the needle (usually a method signature) first and, when the method is missing, falls back to the class.
     *
     * @return callable(string): int
     */
    public static function declaration(): callable
    {
        return FileLineFinder::where(
            fn (string $candidate): bool => preg_match(self::DECLARATION, $candidate) === 1,
        );
    }

    /**
     * The first line containing the needle, falling back to the declaring line.
     *
     * @return callable(string): int
     */
    public static function methodOrClass(string $needle): callable
    {
        return static function (string $path) use ($needle): int {
            $line = FileLineFinder::where(fn (string $candidate): bool => str_contains($candidate, $needle))($path);

            return $line !== 0
                ? $line
                : self::declaration()($path);
        };
    }
}
