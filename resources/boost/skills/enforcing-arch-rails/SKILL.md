---
name: enforcing-arch-rails
description: Use when adding or changing a queued job, notification, mailable, Eloquent model, form request, controller, permission, module directory, or an arch test that demands test coverage, in a project that has dashworthy/pest-plugin-arch-rails installed. The package ships fourteen Pest expectations that enforce dispatch, table-naming, mass-assignment, channel, mail-template, authorization, form-request, API-resource, directory and test-coverage conventions; this skill says which verb covers which case, how to read a failure, and how to scope or exempt a class.
---

# Enforcing architecture rules with pest-plugin-arch-rails

This project has `dashworthy/pest-plugin-arch-rails` installed. It registers
fourteen expectations onto Pest's architecture plugin. They compose with any
`arch()` chain; there is no separate DSL. `toMatchGateAbilities()` is the
exception: its subject is the list of permission names, not a selection of
classes.

## Pick the verb

| You are writing | Assert |
|---|---|
| a job, listener, notification or mailable that should not run inline | `toBeQueued()` |
| something dispatched inside a database transaction | `toBeQueuedAfterCommit()` |
| something deliberately kept synchronous | `toBeSync()` |
| a notification | `toDeclareNotificationChannels()` |
| a mailable | `toUseMarkdownMailTemplates()` |
| an Eloquent model | `toMatchTableName()` and `toGuardMassAssignment()` |
| a form request | `toAuthorizeWithGate()` |
| a controller | `toUseFormRequests()` |
| an API controller | `toReturnJsonResources()` |
| a permission, or a new `Gate::allows()` check | `expect($permissionNames)->toMatchGateAbilities($directories)` |
| a class in a module | `toUseApprovedDirectories($directories, beneath: $modules)` |
| a class in a module directory with a naming suffix | `toUseDirectorySuffixes($suffixes, beneath: $modules)` |
| a rule that every class in a layer has a test | `toHaveTests($map)` |

`toBeSync()` exists so a synchronous choice is asserted rather than merely
absent. Prefer it over silently excluding a class from `toBeQueued()`.

## Write the test

```php
arch('notifications are queued')
    ->expect('App\Domains')
    ->classes()
    ->extending(Illuminate\Notifications\Notification::class)
    ->toBeQueued();
```

Give each verb its own `arch()` call. Pest applies a selector such as
`->extending(...)` only to the first verb in a chain, so a second chained verb
runs against every class in the namespace and fails on the first non-match.

Scope with `->classes()`, `->extending(...)`, `->ignoring('Fully\Qualified\Name')`,
or by targeting a narrower namespace. Target a single class by passing its FQCN
to `expect()`.

## Before you exempt something

An `->ignoring(...)` records only that a class was skipped, never what it was
opted into. Where a positive verb exists, such as `toBeSync()` for a
deliberately synchronous class, assert that in a second rule instead, so the
decision is checked rather than merely tolerated. Never add
`@pest-arch-ignore-line`.

## Three behaviours to know

- **One violation per run.** These are arch expectations; `Blueprint` throws on
  the first failing class. Fix, rerun, repeat.
- **An empty layer passes.** A typo'd namespace turns a rule green. If that
  matters, assert the layer is non-empty in a separate test.
- **`@pest-arch-ignore-line` suppresses any of them**, and cannot be disabled.

## Approved directories are a decision, not a default

A `toUseApprovedDirectories()` rule lists the only directories a module may
have. Treat that list as the project's approved patterns:

1. Put a new class in the approved directory that fits it, and follow the
   classes already there. Where the list names a type for that directory,
   such as `'Grids' => AbstractGrid::class`, the class must extend or
   implement it. Where it names a closure, the class must pass it.
2. If none fits, stop. Ask the user to approve a new directory, name it, and
   say why no approved directory fits.
3. Only once they agree, add the name to the list in the same change that
   first uses it.

Never get past the rule by putting the class directly in the module, by
choosing an approved directory that does not fit or whose type the class
only pretends to be, by widening a directory's closure to let it through, or
by `->ignoring(...)` it.

Where a `toUseDirectorySuffixes()` rule names a directory's suffix, such as
`'Actions' => 'Action'`, end every new class in that directory with it, however
deeply it is nested. A class that fails it is misnamed or misplaced: rename it,
or move it to the directory for what it is.

## Test coverage with `toHaveTests()`

```php
arch('every domain class has a mirrored test')
    ->expect('App\Domains')
    ->toHaveTests([
        app_path('Domains') => base_path('tests/Unit/Domains'),
    ]);
```

| Parameter | Meaning |
|---|---|
| `$map` | `source directory => test directory`, or `=> [several]`. Any one candidate satisfies the class. Longest matching source root wins. |
| `$suffix` | Appended to the source basename to form the test basename. Default `'Test'`. |
| `$skip` | Kinds needing no test: `'abstract'`, `'interface'`, `'trait'`, `'enum'`. `null` means all four. |
| `$exempt` | FQCNs whose **descendants and implementors** are exempt. Naming a class here does not exempt that class itself. |

When it fails, the message names the exact path to create:

```
Expecting a test for this class, but it has no corresponding test. Create
tests/Unit/Domains/Users/CreateUserActionTest.php. ...
```

**The fix is to create the test at that path.** Do not add the class to
`$exempt` to silence it. The other message, *"it is not under any mapped
source root"*, is about the `arch()` test, not the class: widen the map, or
narrow the layer with `->ignoring(...)`.

## What the verbs cannot see

- A `via()` that returns a variable or computed list is trusted: its value is
  not known until it runs.
- An ability held in a variable is invisible to the gate verbs (constants and
  enum `->value` are read). Pass it to `toMatchGateAbilities()` as
  `$alsoChecked` rather than deleting the permission.
- A check with a model argument (`->can('update', $post)`) is a policy check
  and is left out of permission matching.
- Validation inside a closure within a controller action is not read by
  `toUseFormRequests()`.
- Configuration mistakes in a rule (a `**` wildcard, an empty suffix, an
  unknown `$skip` kind, a directory type that does not exist) throw when the
  rule is built. Fix the rule; do not wrap it.

Each area's full behaviour is in the package's `docs/` directory.
