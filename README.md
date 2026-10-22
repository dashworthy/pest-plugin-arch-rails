![pest-plugin-arch-rails — Pest arch rules that keep Laravel code on the rails.](art/banner.svg)

# pest-plugin-arch-rails

Architecture rules for Laravel, written as Pest expectations. They plug straight
into `pest-plugin-arch`: no DSL, no selector grammar, no reporting layer of its
own. Every verb composes with the `arch()` chains you already write.

## Install

```bash
composer require --dev dashworthy/pest-plugin-arch-rails
```

The expectations register themselves through Composer's `autoload.files`. There
is nothing to publish and no service provider.

## Verbs

The package is organised by area. Each area lives in its own directory under
`src/` and registers its own verbs.

| Area | Verb | Passes when |
|---|---|---|
| Messaging | `toBeQueued()` | the class implements `ShouldQueue` (or `ShouldQueueAfterCommit`, which extends it) |
| Messaging | `toBeQueuedAfterCommit()` | the class implements `ShouldQueueAfterCommit` specifically |
| Messaging | `toBeSync()` | the class implements neither: dispatch is deliberately synchronous |
| Messaging | `toDeclareNotificationChannels()` | `via()` exists and returns a non-empty channel list |
| Messaging | `toUseMarkdownMailTemplates()` | `content()` names a markdown template rather than a plain view |
| Models | `toMatchTableName()` | an Eloquent model resolves the table its class name implies |
| Models | `toGuardMassAssignment()` | a model declares `$fillable` or `$guarded`, or relies on the fully guarded default |
| Http | `toAuthorizeWithGate($expectedAbility)` | `authorize()` checks `Gate::allows()` with an ability name, the one `$expectedAbility` derives from the class name when given |
| Http | `toMatchGateAbilities($directories, $alsoChecked)` | (on a list of permission names) every permission is checked by some `Gate::allows()`, and every checked ability is a permission |
| Modules | `toUseApprovedDirectories($directories, $beneath)` | every class in a module sits in an approved directory, and is the type that directory names |
| Modules | `toUseDirectorySuffixes($suffixes, $beneath)` | a class in a module's directory, however deeply nested, ends in the suffix that directory calls for |
| Coverage | `toHaveTests($map, $suffix, $skip, $exempt)` | every class under a mapped source directory has a test file at the mirrored position |

`toMatchGateAbilities()` is the odd one out: it is an ordinary expectation, not an
arch one. Its subject is the list of permission names rather than a selection of
classes, so it reports every mismatch in one run.

`src/Core` holds no verbs. It is the shared base the areas build on: `Rule`
turns an inspector (a class that returns a failure message, or null) into an arch
expectation whose message can name the offending value, and `LineFinder` points
the failure at a useful line.

## Usage

Each verb is an ordinary arch expectation. Point a selector at the Laravel base
class that defines the layer, and the verb checks every class that extends it.

### Notifications are queued and declare their channels

```php
use Illuminate\Notifications\Notification;

arch('every notification is queued and declares its channels')
    ->expect('App')
    ->classes()
    ->extending(Notification::class)
    ->toBeQueued()
    ->toDeclareNotificationChannels();
```

`toBeQueued()` passes for a notification that implements `ShouldQueue` (or
`ShouldQueueAfterCommit`, which extends it); `toDeclareNotificationChannels()`
passes when its `via()` returns a non-empty channel list.

### Mailables render from markdown

```php
use Illuminate\Mail\Mailable;

arch('every mailable uses a markdown template')
    ->expect('App')
    ->classes()
    ->extending(Mailable::class)
    ->toUseMarkdownMailTemplates();
```

The verb recognises only the named-argument form `new Content(markdown: '...')`.
A positional `new Content('mail.welcome')` is reported as a violation even when
it points at a markdown template.

### Models guard mass assignment and match their table

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

### Form requests authorize through a gate

```php
use Illuminate\Foundation\Http\FormRequest;

arch('every form request authorizes through a gate')
    ->expect('App')
    ->classes()
    ->extending(FormRequest::class)
    ->toAuthorizeWithGate();
```

`toAuthorizeWithGate()` passes when `authorize()` calls `Gate::allows()` with a
string literal. Pass a closure to also pin *which* ability: it receives the
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

### Every permission is checked, and every check names a permission

```php
use App\Models\Permission;

test('permissions and gate checks agree', function () {
    expect(Permission::pluck('name')->all())
        ->toMatchGateAbilities([app_path()], alsoChecked: ['viewPulse']);
});
```

`toMatchGateAbilities()` reads every `Gate::allows('...')` call in the PHP files
beneath the directories, outside any `vendor` directory. It fails for a
permission nothing checks (dead weight) and for a checked ability no permission
backs (it can never be granted), listing every mismatch at once. Pass abilities
the source cannot show — names built at runtime, or checked by a package — as
`$alsoChecked`.

### Modules keep to their approved directories

```php
use App\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

arch('every module keeps to the approved directories')
    ->expect('App\Domains')
    ->toUseApprovedDirectories(
        [
            'Actions' => fn (string $class): bool => is_a($class, Action::class, true)
                || is_a($class, ResetsUserPasswords::class, true),
            'Data',
            'Controllers' => Controller::class,
            'Enums' => UnitEnum::class,
            'Models' => Model::class,
            'Requests' => FormRequest::class,
            'Resources' => JsonResource::class,
        ],
        beneath: 'App\Domains\*\*',
    );
```

A directory keyed to a class or interface holds only classes that extend or
implement it. Where one type is not enough, key it to a closure instead: it is
given each class's name and returns `true` to accept it, or `false` or the
reason to reject it.

### Classes are named for their directory

```php
arch('every class is named for the directory it sits in')
    ->expect('App\Domains')
    ->toUseDirectorySuffixes(
        [
            'Actions' => 'Action',
            'Controllers' => 'Controller',
            'Requests' => 'Request',
            'Resources' => 'Resource',
        ],
        beneath: 'App\Domains\*\*',
    );
```

It checks only the directories it lists, and a class nested deeper inside one
is held to the same suffix. Which directories may exist is left to
`toUseApprovedDirectories()`.

### Every class has a mirrored test

The verb takes a whole **layer** — every class beneath a source root — and
requires each to have a test at the mirrored position. It does not single out a
kind of class: an action, a model, and a data object under the root are all
subject to it equally. There is no "Actions directory" to point at; you map the
root, and every class below it is covered.

Point it at a namespace, and the map mirrors each matched class into a test root
at the same relative path. A class at
`App\Domains\Platform\Billing\Actions\PublishPlanVersion` requires a test at
`tests/Unit/Domains/Platform/Billing/Actions/PublishPlanVersionTest.php`, and the
failure names that exact path until it exists:

```php
arch('every domain class has a mirrored test')
    ->expect('App\Domains')
    ->toHaveTests([
        app_path('Domains') => base_path('tests/Unit/Domains'),
    ]);
```

#### Sending one layer to a different suite

The map is an argument, not a config file, and the **longest matching source
root wins**. That lets one layer inside the tree mirror somewhere else without
restating its parent — here, controllers run through the framework, so they
belong in the feature suite while everything else stays in the unit suite:

```php
arch('every domain class has a mirrored test')
    ->expect('App\Domains')
    ->toHaveTests([
        app_path('Domains') => base_path('tests/Unit/Domains'),
        app_path('Domains/Platform/Billing/Controllers')
            => base_path('tests/Feature/Domains/Platform/Billing/Controllers'),
    ]);
```

A controller matches both roots; the longer one wins, so its test is required
under `tests/Feature/Domains/...` and not in the unit suite. A single global
config listing roots could not express "this subtree, but not its parent".

#### Signature

```php
toHaveTests(
    array $map,
    string $suffix = 'Test',
    ?array $skip = null,
    array $exempt = [],
)
```

| Parameter | Meaning |
|---|---|
| `$map` | `source directory => test directory`, or `=> [several]`. Any one candidate satisfies the class. Longest matching source root wins, so a subtree can point at a different test root without restating its parent. |
| `$suffix` | Appended to the source basename to form the test basename. |
| `$skip` | Structural kinds needing no test: `'abstract'`, `'interface'`, `'trait'`, `'enum'`. `null` means all four. Pass a shorter list to demand tests for a kind; pass `[]` to demand them for all. |
| `$exempt` | FQCNs whose **descendants and implementors** are exempt. Naming a class here does not exempt that class itself. |

Directories rather than namespaces, because a Pest test file declares no class:
`class_exists()` is false for every test in a Pest suite, so whether a test exists
is a question about a file.

#### Failures

```
Expecting a test for this class, but it has no corresponding test. Create
/app/tests/Unit/Domains/Users/CreateUserActionTest.php. Or, if it is genuinely
untestable, name a base class in the exempt list, or exclude it with ->ignoring(...).

at app/Domains/Users/CreateUserAction.php:9
```

One violation is reported per run. `Pest\Arch\Blueprint::targeted()` throws inside
its own loop, so a convention failing across forty classes reports the first and
you rerun for the next. Recovering aggregation would mean replacing `Blueprint`,
which this package deliberately does not do.

#### Deliberately not included

- **The reverse direction** — "this test file covers a class that no longer
  exists". Its subject is a Pest test file, and a Pest test file declares no
  class, so it never becomes an object arch can reason about at all.
- **Symlink detection beneath a test root.** It existed to protect a walk of the
  test tree, and there is no such walk in the forward direction. Roots reached
  through a symlink are still resolved, so a package symlinked into `vendor/`
  compares equal to the same directory named literally.
- **A config file.** Every parameter is an argument at the call site.

### Asserting a deliberate decision instead of ignoring it

A class caught by a selector that is *meant* to break the rule can be carved out
with `->ignoring(...)`:

```php
arch('notifications are queued')
    ->expect('App')
    ->classes()
    ->extending(Notification::class)
    ->toBeQueued()
    ->ignoring(App\Notifications\PaymentDeclined::class);
```

But an exclusion only records that `PaymentDeclined` was skipped, not what it was
opted into. When a class is deliberately synchronous, assert that in place with
`toBeSync()` instead — the next reader sees the decision without hunting for the
class:

```php
arch('the payment-declined alert is sent synchronously')
    ->expect(App\Notifications\PaymentDeclined::class)
    ->toBeSync();
```

## What you are accepting

These are arch expectations, so they inherit arch's behaviour:

- **One violation per run.** A rule failing across forty classes reports the
  first and stops.
- **`@pest-arch-ignore-line` suppresses any of them.** Police its use with an
  arch test of your own if that matters to you:

  ```php
  arch('no source file suppresses an architecture rule')
      ->expect('App')
      ->not->toUse('@pest-arch-ignore-line');
  ```
- **An empty layer passes.** A typo'd namespace turns a rule green. Assert the
  layer is non-empty separately if you need that guard.

## Known limitations

Every verb that inspects a model instantiates it via
`newInstanceWithoutConstructor()`; no database connection or booted container
is needed, but anything Eloquent resolves during construction is invisible to
the check. That is the general form of the `#[Fillable]` caveat below.

### `toGuardMassAssignment()`

- **The verb produces a false failure on correct code that declares
  `#[Fillable([...])]` or `#[Guarded([...])]` as a PHP attribute.** It reads
  `getFillable()`/`getGuarded()` from an instance created with
  `newInstanceWithoutConstructor()`, but Eloquent only resolves the attribute
  forms inside `initializeGuardsAttributes()`, which runs during construction.
  With the constructor skipped, an attribute-declared list is invisible and
  the model is reported as unguarded. Exclude any model using the attribute form with `->ignoring(...)`.
- A model that `extends Pivot` inherits `protected $guarded = []` from
  `Illuminate\Database\Eloquent\Relations\Pivot`, so pivots are reported as
  unguarded unless they declare `$fillable` in property form.
- A non-model class caught by the selector is reported as a violation, not
  skipped. Scope the selector or use `->ignoring(...)`.
- Abstract classes pass without being checked.

### `toMatchTableName()`

- A model that does not set `$table` passes **by construction** —
  `getTable()` derives exactly the name the verb expects, so the rule cannot
  fail for it. It only guards against a future `$table` that breaks
  convention; it is not a check on current code.
- A legitimately custom table name (a pivot such as `team_members`, or a
  legacy table) is a violation by design; exclude it with `->ignoring(...)`.
- Same non-model and abstract-class behaviour as `toGuardMassAssignment()`.

### `toDeclareNotificationChannels()` and `toUseMarkdownMailTemplates()`

- Both read the class's own file. A `via()` or `content()` provided by a
  **trait** is not seen — the AST inspected is the class's own file only —
  and is reported as missing.
- Both read `$object->stmts`, which is the AST for the *whole file*, not one
  class body. A second class (or an anonymous class) in the same file can
  satisfy the check on its neighbour's behalf — a false pass, not a false
  failure. Keep one class per file.
- `toUseMarkdownMailTemplates()` additionally scans every argument node
  inside `content()`, so a nested, unrelated call that happens to use a
  `markdown:` named argument also produces a false pass. It also only
  recognises the named-argument form — `new Content(markdown: '...')`. A
  positional `new Content('mail.welcome')` is reported as violating even when
  it does point at a markdown template.

### `toBeQueued()`, `toBeQueuedAfterCommit()`, `toBeSync()`

- No type guard at all: a trait, interface or enum caught by the selector is
  reported as not implementing `ShouldQueue`. Scope the selector.

### `toAuthorizeWithGate()` and `toMatchGateAbilities()`

- Both read source text, not the AST, and see only `Gate::allows()` called with
  a string literal. `Gate::denies()`, `Gate::authorize()`, `$user->can()`,
  policies, `can:` middleware and `@can` are not read, and an ability held in a
  variable or constant is invisible. Pass anything else that is checked through
  `$alsoChecked`.
- Text that merely mentions a `Gate::allows('...')` call with a quoted ability —
  a comment or docblock — is read as a check.
- `toAuthorizeWithGate()` reads `authorize()` from the file that declares it, so
  an inherited `authorize()` is read from the parent. Abstract classes pass
  without being checked.
- `toMatchGateAbilities()` scans every `.php` file beneath the directories,
  which includes Blade views; it skips any path containing a `vendor`
  directory.

### `toHaveTests()`

- There is no reverse direction: nothing asserts that a test file still covers
  a class that exists.

## Editor and agent support

The package ships a Laravel Boost skill, `enforcing-arch-rails`, and a
guideline. Boost discovers them at `resources/boost/skills/` and
`resources/boost/guidelines/` for every installed package, so a consuming
project picks them up by running:

```bash
php artisan boost:update
```

The skill explains which verb covers which case and how to scope or exempt one;
the guideline is the short form injected into the project's AI guidelines.

## Licence

MIT.
