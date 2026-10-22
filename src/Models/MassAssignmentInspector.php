<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Models;

use Dashworthy\PestPluginArchRails\Core\Inspector;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Architecture\Elements\ObjectDescription;
use Throwable;

/**
 * Checks that a model restricts mass assignment through $fillable/$guarded
 * or the #[Fillable]/#[Guarded] attributes.
 */
final class MassAssignmentInspector implements Inspector
{
    public const string NOT_A_MODEL = 'Expecting an Eloquent model, but this class is not one. Narrow the selection with ->extending(Illuminate\Database\Eloquent\Model::class).';

    public const string UNGUARDED = 'Expecting the model to restrict mass assignment, but it declares an empty $guarded with no $fillable allow-list, unguarding every attribute. Add a $fillable allow-list, or remove the empty $guarded declaration to rely on the fully guarded default.';

    public function __invoke(ObjectDescription $object): ?string
    {
        $class = $object->reflectionClass;

        if (! $class->isSubclassOf(Model::class)) {
            return self::NOT_A_MODEL;
        }

        if ($class->isAbstract()) {
            return null;
        }

        try {
            $model = ModelReflector::instantiate($class);
        } catch (Throwable $throwable) {
            return sprintf("Expecting the model's mass-assignment rules to be readable, but reading them threw %s: %s.", $throwable::class, $throwable->getMessage());
        }

        return $model->getFillable() !== [] || ModelReflector::declaredGuarded($model) !== []
            ? null
            : self::UNGUARDED;
    }
}
