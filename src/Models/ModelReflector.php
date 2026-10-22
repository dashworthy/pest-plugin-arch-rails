<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Models;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionProperty;

/**
 * Builds a model without running its constructor, then applies the class
 * attributes (#[Table], #[Fillable], #[Guarded], #[Unguarded]) exactly as
 * Eloquent's own initializers would.
 */
final class ModelReflector
{
    /**
     * @param  ReflectionClass<covariant object>  $class
     */
    public static function instantiate(ReflectionClass $class): Model
    {
        $model = $class->newInstanceWithoutConstructor();

        assert($model instanceof Model);

        foreach (['initializeModelAttributes', 'initializeGuardsAttributes'] as $initializer) {
            if (method_exists($model, $initializer)) {
                $model->{$initializer}();
            }
        }

        return $model;
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
