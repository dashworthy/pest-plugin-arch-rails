# Modules

Directory and naming rules for modular (DDD) code.

## Modules keep to their approved directories

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
implement it. A directory keyed to `null` (`'Data' => null`) is approved
for any class, the same as listing its name alone. Where one type is not enough, key it to a closure instead: it is
given each class's name and returns `true` to accept it, or `false` or the
reason to reject it.

## Classes are named for their directory

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

## The `$beneath` pattern

`$beneath` is a namespace in which `*` matches exactly one segment, such as
`'App\Domains\*\*'`. Segments compare case-insensitively, as PHP namespaces
do. `**` and partial wildcards such as `Foo*` are rejected with an error rather
than silently matching nothing.

## Configuration errors

Mistakes in the rule itself throw as soon as the rule is built, once, rather
than failing every class:

- an empty directory name, or a closure given without a directory key;
- a directory type naming a class that does not exist;
- an empty suffix, a suffix without a directory key, or a suffix containing `\`.

## Edges

- A directory that differs from an approved one only by case fails with a
  message saying so.
- An exception thrown inside a directory closure is reported as a violation
  naming the class and the exception.
- Suffixes are compared against the short class name only.
