# Http

Form requests, controllers, API resources and permissions.

## Form requests authorize through a gate

```php
use Illuminate\Foundation\Http\FormRequest;

arch('every form request authorizes through a gate')
    ->expect('App')
    ->classes()
    ->extending(FormRequest::class)
    ->toAuthorizeWithGate();
```

`toAuthorizeWithGate()` passes when the value `authorize()` returns depends on a
gate check: every `return` is a check, its negation, `->allowed()` on one, or an
`&&` containing one. A `Gate::authorize('...')` statement, which throws when
denied, also counts. `Gate::allows('x') || true`, or a check whose result is
thrown away before `return true`, is reported. Pass a closure to also pin *which* ability: it receives the
request's class name and returns the ability it should check, or `null` to
accept any.

```php
use Illuminate\Support\Str;

// StoreWidgetRequest must check 'widgets_store'.
$ability = function (string $request): ?string {
    if (! preg_match('/^(Index|Show|Store|Update|Destroy)(\w+)Request$/', class_basename($request), $parts)) {
        return null;
    }

    return Str::snake(Str::plural($parts[2])).'_'.strtolower($parts[1]);
};

arch('every form request checks the ability its name implies')
    ->expect('App\Http\Requests')
    ->classes()
    ->extending(FormRequest::class)
    ->toAuthorizeWithGate($ability);
```

## Controllers validate through form requests

```php
arch('every controller validates through a form request')
    ->expect('App\Http\Controllers')
    ->toUseFormRequests();
```

`toUseFormRequests()` checks every public action on a controller, including
`__invoke()` and actions that come from a trait. An action fails when it:

- takes a plain `Illuminate\Http\Request` (a form request, or any other
  subclass, is fine), or
- validates in its own body with `$request->validate()`, `$this->validate()`,
  `request()->validate()`, `validateWithBag()`, `Validator::make()` or
  `validator()`.

An action that takes no request at all, such as `show(Widget $widget)`, passes.
Static methods, constructors and other magic methods, abstract classes, and
methods inherited from `Illuminate\Routing\Controller` are not checked.
Validation inside a closure within an action is not read.

## API controllers return resources

```php
arch('every API controller returns a resource')
    ->expect('App\Http\Controllers\Api')
    ->toReturnJsonResources();
```

`toReturnJsonResources()` checks the declared return type of every public
action. It must be a `JsonResource` subclass: your own resource, a
`ResourceCollection`, or the `AnonymousResourceCollection` that
`Resource::collection()` builds. PHP enforces the return type, so an action that
passes cannot return a raw array, a model or a `JsonResponse`.

- A nullable resource (`?WidgetResource`) passes. In a union, every other
  member must be a resource.
- An action with no return type fails.
- Your resource classes are recognised even where the framework is not
  installed: their parents are read from source through Composer.

## Every permission is checked, and every check names a permission

```php
use App\Models\Permission;

test('permissions and gate checks agree', function () {
    expect(Permission::pluck('name')->all())
        ->toMatchGateAbilities([app_path()], alsoChecked: ['viewPulse']);
});
```

`toMatchGateAbilities()` reads every ability check in the PHP files beneath the
directories, outside any `vendor` directory: `Gate::allows`, `denies`, `check`,
`authorize`, `inspect`, `any` and `none`; `->can()`, `->cannot()` and `->cant()`
on any object; and `'can:...'` middleware strings. Blade views are scanned for
`@can`, `@cannot`, `@canany`, `@elsecan` and `@elsecannot`. Comments never count. It fails for a
permission nothing checks (dead weight) and for a checked ability no permission
backs (it can never be granted), listing every mismatch at once. Pass abilities
the source cannot show — names built at runtime, or checked by a package — as
`$alsoChecked`.

## Edges

- Checks are read from the parsed code, so aliased imports (`use Gate as G`),
  the fully qualified facade and the global `Gate` alias all resolve, while a
  class that merely ends in `Gate` does not count.
- An ability held in a variable or constant cannot be read. Pass it to
  `toMatchGateAbilities()` as `$alsoChecked`.
- A permission listed twice is reported once. A directory that does not exist
  fails the expectation with a readable message.
