<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Http;

use ReflectionClass;
use ReflectionMethod;

/**
 * The public actions a controller exposes to routes.
 */
final class ControllerActions
{
    /**
     * Methods inherited from these classes are framework plumbing, not actions.
     *
     * @var list<string>
     */
    private const array FRAMEWORK_CLASSES = [
        'Illuminate\Routing\Controller',
    ];

    /**
     * Every public, non-static, concrete method other than a magic method, though __invoke() counts. None for an
     * abstract class, interface, trait or enum, since only a concrete class is routed to.
     *
     * @param  ReflectionClass<object>  $class
     * @return list<ReflectionMethod>
     */
    public static function of(ReflectionClass $class): array
    {
        if ($class->isAbstract() || $class->isInterface() || $class->isTrait() || $class->isEnum()) {
            return [];
        }

        $frameworkTraits = self::frameworkTraits($class);

        return array_values(array_filter(
            $class->getMethods(ReflectionMethod::IS_PUBLIC),
            fn (ReflectionMethod $method): bool => self::isAction($method, $frameworkTraits),
        ));
    }

    /**
     * @param  list<ReflectionClass<object>>  $frameworkTraits
     */
    private static function isAction(ReflectionMethod $method, array $frameworkTraits): bool
    {
        $name = $method->getName();

        if ($method->isStatic() || $method->isAbstract() || (str_starts_with($name, '__') && $name !== '__invoke')) {
            return false;
        }

        if (in_array($method->getDeclaringClass()->getName(), self::FRAMEWORK_CLASSES, true)) {
            return false;
        }

        return ! array_any($frameworkTraits, fn (ReflectionClass $trait): bool => self::comesFrom($method, $trait));
    }

    /**
     * Whether the method is the trait's own, rather than an override the controller wrote. PHP reports a trait
     * method as declared by the using class, so the method is matched by name and source location instead.
     *
     * @param  ReflectionClass<object>  $trait
     */
    private static function comesFrom(ReflectionMethod $method, ReflectionClass $trait): bool
    {
        if (! $trait->hasMethod($method->getName())) {
            return false;
        }

        $own = $trait->getMethod($method->getName());

        return $own->getFileName() === $method->getFileName() && $own->getStartLine() === $method->getStartLine();
    }

    /**
     * Every trait in the Illuminate namespace the class uses, through its parents and nested traits.
     *
     * @param  ReflectionClass<object>  $class
     * @return list<ReflectionClass<object>>
     */
    private static function frameworkTraits(ReflectionClass $class): array
    {
        $found = [];
        $pending = [];

        for ($current = $class; $current instanceof ReflectionClass; $current = $current->getParentClass() ?: null) {
            array_push($pending, ...array_values($current->getTraits()));
        }

        while ($pending !== []) {
            $trait = array_pop($pending);

            if (isset($found[$trait->getName()])) {
                continue;
            }

            $found[$trait->getName()] = $trait;
            array_push($pending, ...array_values($trait->getTraits()));
        }

        return array_values(array_filter(
            $found,
            fn (ReflectionClass $trait): bool => str_starts_with($trait->getName(), 'Illuminate\\'),
        ));
    }
}
