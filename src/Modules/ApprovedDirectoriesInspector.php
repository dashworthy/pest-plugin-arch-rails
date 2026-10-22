<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Modules;

use Closure;
use InvalidArgumentException;
use PHPUnit\Architecture\Elements\ObjectDescription;
use Throwable;

/**
 * Decides whether a class sits in one of the approved directories beneath a parent namespace, such as the
 * Actions, Models and Controllers directories every module of a modular application repeats, and whether it is the
 * kind of class that directory holds.
 *
 * The parent is a namespace pattern in which * matches any one segment, so 'App\Domains\*\*' names every module two
 * levels beneath App\Domains. Only the directory directly beneath the parent is approved or not; a directory may also
 * name the class or interface everything inside it, however deeply nested, must extend or implement, or a closure
 * that checks each class there itself. A class outside the parent is not this rule's concern.
 */
final readonly class ApprovedDirectoriesInspector
{
    /**
     * Said with every failure: the list is a decision, so a new directory is asked for, not added.
     */
    public const string APPROVAL = 'If none fits, ask for approval before creating a new directory or adding one to the approved list.';

    /** @var array<string, class-string|Closure(class-string): (bool|string)|null> */
    private array $directories;

    private ModulePattern $modules;

    /**
     * A name given as a key names the class or interface every class in that directory must be, or a closure that is
     * given each class's name there and returns true to accept it, or false or the reason to reject it.
     *
     * @param  array<mixed>  $directories  The approved directory names.
     * @param  string  $beneath  The parent namespace, * matching any one segment.
     *
     * @throws InvalidArgumentException When a directory name, type or closure is misconfigured.
     */
    public function __construct(array $directories, string $beneath)
    {
        $approved = [];

        foreach ($directories as $key => $value) {
            if (is_int($key)) {
                if ($value instanceof Closure) {
                    throw new InvalidArgumentException(sprintf('The closure at position %d of the approved directories has no directory name. Key it by the directory it checks, such as \'Actions\' => fn (string $class) => ...', $key));
                }

                if (! is_string($value) || $value === '') {
                    throw new InvalidArgumentException(sprintf('The approved directory at position %d must be a non-empty directory name.', $key));
                }

                $approved[$value] = null;

                continue;
            }

            if ($key === '') {
                throw new InvalidArgumentException('An approved directory name must not be empty.');
            }

            if ($value instanceof Closure) {
                /** @var Closure(class-string): (bool|string) $value */
                $approved[$key] = $value;

                continue;
            }

            if (! is_string($value) || ! (class_exists($value) || interface_exists($value) || enum_exists($value))) {
                throw new InvalidArgumentException(sprintf(
                    'The type given for the approved %s directory, %s, does not exist. Name an existing class or interface, or give a closure.',
                    $key,
                    is_string($value) ? $value : get_debug_type($value),
                ));
            }

            $approved[$key] = $value;
        }

        $this->directories = $approved;
        $this->modules = new ModulePattern($beneath);
    }

    /**
     * The failure message, or null when the class satisfies the rule.
     */
    public function __invoke(ObjectDescription $object): ?string
    {
        $location = $this->modules->locate($object->name);

        if ($location === null) {
            return null;
        }

        $parent = $location['module'];
        $directory = $location['directory'];

        if ($directory === null) {
            return sprintf(
                'Expecting the class to sit in an approved directory of %s, but it sits directly in %s. %s %s',
                $parent,
                $parent,
                $this->approved(),
                self::APPROVAL,
            );
        }

        if (! array_key_exists($directory, $this->directories)) {
            $match = $this->caseInsensitiveMatch($directory);

            if ($match !== null) {
                return sprintf(
                    "Expecting the class to sit in an approved directory of %s, but '%s' differs only in case from approved '%s'. Rename the directory and namespace to '%s'.",
                    $parent,
                    $directory,
                    $match,
                    $match,
                );
            }

            return sprintf(
                "Expecting the class to sit in an approved directory of %s, but '%s' is not one. %s %s",
                $parent,
                $directory,
                $this->approved(),
                self::APPROVAL,
            );
        }

        $type = $this->directories[$directory];

        if ($type instanceof Closure) {
            return $this->check($type, $object->name, $directory);
        }

        if ($type !== null && ! is_a($object->name, $type, true)) {
            return sprintf(
                'Expecting every class in the %s directory to be a %s, but this one is not. Extend or implement %s, or move the class to the approved directory for what it is. %s',
                $directory,
                $type,
                $type,
                self::APPROVAL,
            );
        }

        return null;
    }

    /**
     * Anything but true rejects the class, so a closure that forgets to return fails rather than passes.
     *
     * @param  Closure(class-string): (bool|string)  $check
     * @param  class-string  $class
     */
    private function check(Closure $check, string $class, string $directory): ?string
    {
        try {
            $result = $check($class);
        } catch (Throwable $exception) {
            return sprintf(
                'Expecting every class in the %s directory to pass the check its approved directory names, but the check threw %s for %s: %s',
                $directory,
                $exception::class,
                $class,
                $exception->getMessage(),
            );
        }

        if ($result === true) {
            return null;
        }

        return sprintf(
            'Expecting every class in the %s directory to pass the check its approved directory names, but this one does not%s. Change the class to pass it, or move the class to the approved directory for what it is. %s',
            $directory,
            is_string($result) && rtrim($result, '. ') !== '' ? ': '.rtrim($result, '. ') : '',
            self::APPROVAL,
        );
    }

    private function caseInsensitiveMatch(string $directory): ?string
    {
        foreach (array_keys($this->directories) as $approved) {
            if (strcasecmp($approved, $directory) === 0) {
                return $approved;
            }
        }

        return null;
    }

    private function approved(): string
    {
        $directories = array_map(strval(...), array_keys($this->directories));

        if ($directories === []) {
            return 'No directories are approved.';
        }

        sort($directories);

        return 'Use one of: '.implode(', ', $directories).'.';
    }
}
