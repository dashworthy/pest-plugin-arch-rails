<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Models;

use Illuminate\Database\Eloquent\Attributes\Initialize;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * Builds a model without running its constructor, then applies the class
 * attributes (#[Table], #[Fillable], #[Guarded], #[Unguarded]) and runs every trait
 * initializer exactly as Eloquent's own initializeTraits() would.
 */
final class ModelReflector
{
    private const string INITIALIZE = Initialize::class;

    /**
     * @param  ReflectionClass<covariant object>  $class
     */
    public static function instantiate(ReflectionClass $class): Model
    {
        $model = $class->newInstanceWithoutConstructor();

        assert($model instanceof Model);

        foreach (self::initializers($class) as $initializer) {
            try {
                $class->getMethod($initializer)->invoke($model);
            } catch (Throwable $throwable) {
                throw new RuntimeException(sprintf('initializer %s() threw %s: %s', $initializer, $throwable::class, $throwable->getMessage()), 0, $throwable);
            }
        }

        return $model;
    }

    /**
     * The constructor's class-attribute initializers, then those Eloquent's bootTraits() collects: initialize{Trait}() for every trait used, plus
     * any method marked #[Initialize]. Static boot methods are not run, so nothing needing the container is touched.
     *
     * @param  ReflectionClass<covariant object>  $class
     * @return list<string>
     */
    private static function initializers(ReflectionClass $class): array
    {
        $conventional = array_map(
            static fn (string $trait): string => 'initialize'.class_basename($trait),
            array_values(class_uses_recursive($class->getName())),
        );

        $initializers = array_values(array_filter(
            ['initializeModelAttributes', 'initializeGuardsAttributes'],
            $class->hasMethod(...),
        ));

        foreach ($class->getMethods() as $method) {
            if ($method->isStatic()) {
                continue;
            }

            if (in_array($method->getName(), $conventional, true)
                || (class_exists(self::INITIALIZE) && $method->getAttributes(self::INITIALIZE) !== [])) {
                $initializers[] = $method->getName();
            }
        }

        return array_values(array_unique($initializers));
    }

    /**
     * The guarded list the model declares, ignoring a global Model::unguard().
     *
     * @return array<array-key, mixed>
     */
    public static function declaredGuarded(Model $model): array
    {
        $guarded = new ReflectionProperty(Model::class, 'guarded')->getValue($model);

        return is_array($guarded) ? $guarded : [];
    }
}
