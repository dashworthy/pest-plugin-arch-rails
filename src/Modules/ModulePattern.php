<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Modules;

use InvalidArgumentException;

/**
 * The modules of a modular application, named by a namespace pattern in which * matches any one segment (** is rejected), compared case-insensitively as PHP compares namespaces, so
 * 'App\Domains\*\*' names every module two levels beneath App\Domains. It finds where a class sits relative to them:
 * which module, and which directory directly beneath that module.
 */
final readonly class ModulePattern
{
    /** @var array<int, string> */
    private array $segments;

    /**
     * @throws InvalidArgumentException When the pattern is empty, has an empty segment, or uses ** or a partial wildcard.
     */
    public function __construct(string $pattern)
    {
        $trimmed = trim($pattern, '\\');

        if ($trimmed === '') {
            throw new InvalidArgumentException('The parent namespace pattern must not be empty.');
        }

        $segments = explode('\\', $trimmed);

        foreach ($segments as $segment) {
            if ($segment === '') {
                throw new InvalidArgumentException(sprintf("The parent namespace pattern '%s' has an empty segment.", $pattern));
            }

            if ($segment !== '*' && str_contains($segment, '*')) {
                throw new InvalidArgumentException(sprintf(
                    "The parent namespace pattern '%s' uses '%s', but only * on its own, matching exactly one segment, is supported. Write one * per segment, such as 'App\\Domains\\*\\*'.",
                    $pattern,
                    $segment,
                ));
            }
        }

        $this->segments = $segments;
    }

    /**
     * The module a class sits in and the directory directly beneath that module, the directory null when the class
     * sits directly in the module. Null when the class is outside every module.
     *
     * @return array{module: string, directory: string|null}|null
     */
    public function locate(string $class): ?array
    {
        $segments = explode('\\', ltrim($class, '\\'));
        array_pop($segments);

        $depth = count($this->segments);

        if (count($segments) < $depth) {
            return null;
        }

        foreach ($this->segments as $index => $pattern) {
            if ($pattern !== '*' && strcasecmp($pattern, $segments[$index]) !== 0) {
                return null;
            }
        }

        return [
            'module' => implode('\\', array_slice($segments, 0, $depth)),
            'directory' => $segments[$depth] ?? null,
        ];
    }
}
