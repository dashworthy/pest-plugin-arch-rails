# Models

Eloquent table names and mass assignment.

## Models guard mass assignment and match their table

```php
use Illuminate\Database\Eloquent\Model;

arch('every model is safe and conventional')
    ->expect('App')
    ->classes()
    ->extending(Model::class)
    ->toGuardMassAssignment()
    ->toMatchTableName();
```

`toGuardMassAssignment()` passes for a model that declares `$fillable` or
`$guarded`, or relies on the fully guarded default; `toMatchTableName()` passes
when the class name resolves the table Laravel would derive from it.

## Edges

- `$table`, the `#[Table]` attribute and an overridden `getTable()` are all
  honoured. A table qualified with a schema (`reporting.widgets`) is compared
  by the part after the last dot.
- A `Pivot` without `$table` is expected to use Eloquent's singular pivot name
  (`role_user`); a `MorphPivot` and ordinary models use the plural.
- `$fillable`, `$guarded`, and the `#[Fillable]`, `#[Guarded]` and
  `#[Unguarded]` attributes are read the way Eloquent reads them, including
  from parent classes. A global `Model::unguard()` left on elsewhere does not
  affect the result.
- A `getTable()` that throws, for example because it needs the container, is
  reported as a violation naming the exception.
- A class caught by the selector that is not a model is reported, not skipped.
  Abstract models pass without being checked. A custom table name is a
  violation by design: exclude it with `->ignoring(...)`.
