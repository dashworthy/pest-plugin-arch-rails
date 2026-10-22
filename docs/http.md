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
gate check. Every `return` must be one of:

- a check, such as `Gate::allows(...)`, `Gate::forUser($user)->allows(...)` or
  `$this->user()->can(...)`, or `->allowed()` on `Gate::inspect(...)`;
- `false`, since refusing never grants access;
- an `&&` with a deciding side, or an `||` or ternary whose sides all decide.

Negation is tracked, so `! (Gate::allows('x') && $other)`, which grants access
whenever `$other` is false, is reported. Instead of returning a check,
`authorize()` may call `Gate::authorize('...')`, which throws when denied, as a
statement before any `return`. `Gate::allows('x') || true`, a check whose
result is thrown away, or an early `return true` before the guard is reported.
Returns inside closures and anonymous classes are not `authorize()`'s returns.

The ability may be a string, a class constant (`self::ABILITY`) or an enum's
`->value`. Pass a closure to also pin *which* ability: it receives the
request's class name and returns the ability it should check, or `null` to
accept any. When a closure is given and the ability cannot be read from source,
the request is reported.

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

`toMatchGateAbilities()` reads the ability checks in the PHP files beneath the
directories, outside any `vendor` directory:

- `Gate::allows`, `denies`, `check`, `authorize`, `inspect`, `any` and `none`,
  also through `Gate::forUser($user)`;
- `->can()`, `->cannot()`, `->cant()` and `->canAny()` on any object;
- `$this->authorize()` and `$this->authorizeForUser()` in controllers;
- `'can:...'` middleware strings, and the `#[Authorize('...')]` attribute or
  `Authorize::using('...')`;
- in Blade views, `@can`, `@cannot`, `@canany`, `@elsecan`, `@elsecanany` and
  `@elsecannot`.

It fails for a permission nothing checks (dead weight) and for a checked
ability no permission backs (it can never be granted), listing every mismatch
at once. Comments, including Blade `{{-- --}}` comments, never count.

A check that passes a model or other argument after the ability, such as
`->can('update', $post)`, `@can('view', $post)` or `'can:update,post'`, is a
policy check, not a permission. It is left out of permission matching
entirely.

Pass abilities the source cannot show, such as names built at runtime or
checked by a package, as `$alsoChecked`.

## Edges

- Checks are read from the parsed code, so aliased imports (`use Gate as G`),
  the fully qualified facade and the global `Gate` alias all resolve, while a
  class that merely ends in `Gate` does not count.
- Class constants and enum `->value` abilities are resolved by reflection. An
  ability held in a variable, or a double-quoted string with interpolation,
  cannot be read. Pass it to `toMatchGateAbilities()` as `$alsoChecked`.
- A PHP file is parsed as written, so a plain-PHP view that starts with HTML is
  read too. Files mentioning none of `gate`, `can` or `authorize` are skipped
  without parsing.
- A permission listed twice is reported once. A directory that does not exist
  fails the expectation with a readable message.
