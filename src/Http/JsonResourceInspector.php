<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Http;

use Dashworthy\PestPluginArchRails\Core\Inspector;
use PHPUnit\Architecture\Elements\ObjectDescription;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * Decides whether every action on a controller declares that it returns an API resource.
 *
 * The declared return type is what PHP enforces, so an action typed to return a JsonResource subclass (a resource,
 * a ResourceCollection, or the AnonymousResourceCollection that Resource::collection() builds) cannot return a raw
 * array, model or JsonResponse. A nullable type is accepted; every other member of a union must be a resource.
 */
final readonly class JsonResourceInspector implements Inspector
{
    private const string JSON_RESOURCE = 'Illuminate\\Http\\Resources\\Json\\JsonResource';

    /**
     * The framework's resource classes. ResourceCollection and AnonymousResourceCollection extend JsonResource, but
     * are named here so a class extending them is recognised when the framework itself is not installed.
     *
     * @var list<string>
     */
    private const array RESOURCE_CLASSES = [
        self::JSON_RESOURCE,
        'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
    ];

    public function __invoke(ObjectDescription $object): ?string
    {
        foreach (ControllerActions::of($object->reflectionClass) as $method) {
            $failure = $this->inspectAction($method);

            if ($failure !== null) {
                return $failure;
            }
        }

        return null;
    }

    private function inspectAction(ReflectionMethod $method): ?string
    {
        $type = $method->getReturnType();

        if (! $type instanceof ReflectionType) {
            return sprintf(
                'Expecting %s() to return an API resource, but it declares no return type. Declare a %s subclass as its return type.',
                $method->getName(),
                self::JSON_RESOURCE,
            );
        }

        $members = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($members as $member) {
            if (! $member instanceof ReflectionNamedType) {
                return $this->notAResource($method, (string) $type);
            }

            if ($member->getName() === 'null') {
                continue;
            }

            if ($member->isBuiltin() || ! $this->isResource($member->getName())) {
                return $this->notAResource($method, $member->getName());
            }
        }

        return null;
    }

    private function isResource(string $class): bool
    {
        return array_any(self::RESOURCE_CLASSES, fn (string $resource): bool => ClassAncestry::extends($class, $resource));
    }

    private function notAResource(ReflectionMethod $method, string $type): string
    {
        return sprintf(
            'Expecting %s() to return an API resource, but it declares %s. Return a %s subclass instead.',
            $method->getName(),
            $type,
            self::JSON_RESOURCE,
        );
    }
}
