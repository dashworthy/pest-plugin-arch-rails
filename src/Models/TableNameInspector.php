<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Models;

use Dashworthy\PestPluginArchRails\Core\Inspector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;
use PHPUnit\Architecture\Elements\ObjectDescription;
use Throwable;

/**
 * Checks that a model's table is the name Eloquent derives from its class.
 */
final class TableNameInspector implements Inspector
{
    public const string NOT_A_MODEL = 'Expecting an Eloquent model, but this class is not an Eloquent model. Narrow the selection with ->extending(Illuminate\Database\Eloquent\Model::class).';

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
            $actual = ModelReflector::instantiate($class)->getTable();
        } catch (Throwable $throwable) {
            return sprintf(
                "Expecting the model's table name to be resolvable, but resolving it threw %s: %s. Declare the table with \$table or #[Table], or exclude this class with ->ignoring(...).",
                $throwable::class,
                $throwable->getMessage(),
            );
        }

        $expected = $this->derivedTable($class->getName());
        $unqualified = Str::afterLast($actual, '.');

        if ($unqualified === $expected) {
            return null;
        }

        return sprintf(
            "Expecting the table to follow the naming convention, but the model resolves table '%s', expected '%s'. Rename the table, or exclude this class with ->ignoring(...).",
            $actual,
            $expected,
        );
    }

    /**
     * The table Eloquent derives when the model declares none.
     */
    private function derivedTable(string $class): string
    {
        $basename = class_basename($class);

        $isPivot = is_subclass_of($class, Pivot::class) || in_array(AsPivot::class, class_uses_recursive($class), true);

        if ($isPivot && ! is_subclass_of($class, MorphPivot::class)) {
            return str_replace('\\', '', Str::snake(Str::singular($basename)));
        }

        return Str::snake(Str::pluralStudly($basename));
    }
}
