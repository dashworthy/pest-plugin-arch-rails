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

        return array_values(array_filter(
            $class->getMethods(ReflectionMethod::IS_PUBLIC),
            self::isAction(...),
        ));
    }

    private static function isAction(ReflectionMethod $method): bool
    {
        $name = $method->getName();

        if ($method->isStatic() || $method->isAbstract() || (str_starts_with($name, '__') && $name !== '__invoke')) {
            return false;
        }

        return ! in_array($method->getDeclaringClass()->getName(), self::FRAMEWORK_CLASSES, true);
    }
}
