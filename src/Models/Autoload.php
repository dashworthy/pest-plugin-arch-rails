<?php

declare(strict_types=1);

use Dashworthy\PestPluginArchRails\Core\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Pest\Arch\Contracts\ArchExpectation;
use PHPUnit\Architecture\Elements\ObjectDescription;

/*
 | Each closure MUST declare ": ArchExpectation" as its return type.
 | Pest\Expectation::__call inspects that exact return type to decide whether to
 | invoke the closure directly; without it the call is routed through
 | ExpectationPipeline and the lazy arch evaluation never runs.
 */

expect()->extend('toMatchTableName', function (): ArchExpectation {
    return Rule::make(
        $this,
        function (ObjectDescription $object, ?string &$detail): bool {
            if (! $object->reflectionClass->isSubclassOf(Model::class)) {
                $detail = 'not-a-model';

                return false;
            }

            if ($object->reflectionClass->isAbstract()) {
                return true;
            }

            /** @var Model $model */
            $model = $object->reflectionClass->newInstanceWithoutConstructor();

            $expected = Str::snake(Str::pluralStudly(class_basename($object->name)));
            $actual = $model->getTable();

            $detail = sprintf('%s|%s', $actual, $expected);

            return $actual === $expected;
        },
        function ($violation, ?string $detail): string {
            if ($detail === 'not-a-model') {
                return 'Expecting an Eloquent model, but this class is not an Eloquent model. Narrow the selection with ->extending(Illuminate\Database\Eloquent\Model::class).';
            }

            [$actual, $expected] = explode('|', (string) $detail, 2);

            return sprintf(
                "Expecting the table to follow the naming convention, but the model resolves table '%s', expected '%s'. Rename the table, or exclude this class with ->ignoring(...).",
                $actual,
                $expected,
            );
        },
    );
});

expect()->extend('toGuardMassAssignment', function (): ArchExpectation {
    return Rule::make(
        $this,
        function (ObjectDescription $object, ?string &$detail): bool {
            if (! $object->reflectionClass->isSubclassOf(Model::class)) {
                $detail = 'not-a-model';

                return false;
            }

            if ($object->reflectionClass->isAbstract()) {
                return true;
            }

            /** @var Model $model */
            $model = $object->reflectionClass->newInstanceWithoutConstructor();

            return $model->getFillable() !== [] || $model->getGuarded() !== [];
        },
        fn ($violation, ?string $detail): string => $detail === 'not-a-model'
            ? 'Expecting an Eloquent model, but this class is not one. Narrow the selection with ->extending(Illuminate\Database\Eloquent\Model::class).'
            : 'Expecting the model to restrict mass assignment, but it declares an empty $guarded with no $fillable allow-list, unguarding every attribute. Add a $fillable allow-list, or remove the empty $guarded declaration to rely on the fully guarded default.',
    );
});
