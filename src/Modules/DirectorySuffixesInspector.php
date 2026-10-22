<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Modules;

use InvalidArgumentException;
use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Decides whether a class's name ends in the suffix its directory calls for, such as Action for every class in a
 * module's Actions directory, however deeply it is nested there.
 *
 * Only the directories given a suffix are checked. A class in any other directory, directly in a module, or outside
 * the modules is not this rule's concern; toUseApprovedDirectories() decides which directories may exist.
 */
final readonly class DirectorySuffixesInspector
{
    private ModulePattern $modules;

    /** @var array<string, string> */
    private array $suffixes;

    /**
     * @param  array<mixed>  $suffixes  The suffix each directory's classes end in, keyed by directory name.
     * @param  string  $beneath  The parent namespace, * matching any one segment.
     */
    public function __construct(array $suffixes, string $beneath)
    {
        $validated = [];

        foreach ($suffixes as $directory => $suffix) {
            if (! is_string($directory) || $directory === '') {
                throw new InvalidArgumentException(sprintf('Each suffix must be keyed by its directory name, but %s is not a directory name.', var_export($directory, true)));
            }

            if (! is_string($suffix) || $suffix === '') {
                throw new InvalidArgumentException(sprintf('The suffix for the %s directory must be a non-empty string; an empty suffix would pass every class.', $directory));
            }

            if (str_contains($suffix, '\\')) {
                throw new InvalidArgumentException(sprintf("The suffix '%s' for the %s directory contains a namespace separator, but suffixes are matched against the short class name only.", $suffix, $directory));
            }
            $validated[$directory] = $suffix;
        }

        $this->suffixes = $validated;
        $this->modules = new ModulePattern($beneath);
    }

    /**
     * The failure message, or null when the class satisfies the rule.
     */
    public function __invoke(ObjectDescription $object): ?string
    {
        $directory = $this->modules->locate($object->name)['directory'] ?? null;

        if ($directory === null || ! array_key_exists($directory, $this->suffixes)) {
            return null;
        }

        $suffix = $this->suffixes[$directory];
        $name = substr((string) strrchr('\\'.$object->name, '\\'), 1);

        if (str_ends_with($name, $suffix)) {
            return null;
        }

        return sprintf(
            "Expecting every class in the %s directory to end in '%s', but %s does not. Rename it to end in '%s', or move it to the directory for what it is.",
            $directory,
            $suffix,
            $name,
            $suffix,
        );
    }
}
