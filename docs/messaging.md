# Messaging

Queued jobs, notifications and mailables.

## Notifications are queued and declare their channels

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

## Mailables render from markdown

```php
use Illuminate\Mail\Mailable;

arch('every mailable uses a markdown template')
    ->expect('App')
    ->classes()
    ->extending(Mailable::class)
    ->toUseMarkdownMailTemplates();
```

The verb accepts `new Content(markdown: '...')`, a markdown template passed
positionally in `Content`'s markdown slot, and the fluent
`(new Content)->markdown('...')`. It rejects `markdown: null`, a plain `view:`,
and a `markdown:` argument passed to some unrelated call inside `content()`.

## Asserting a deliberate decision instead of ignoring it

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

## Edges

- A queue verb given an enum, interface or trait fails with "Expecting a
  class", since only classes are dispatched.
- `via()` and `content()` are found the way PHP finds them: through parent
  classes and traits, and regardless of the case they are declared in.
- A `return` inside a closure, arrow function or anonymous class within `via()`
  is not one of `via()`'s returns.
- `via()` returning a variable or a computed list is trusted, since its value
  cannot be read without running it. An abstract `via()` passes; each concrete
  subclass is checked on its own.
- A legacy mailable with `build()` and no `content()` fails: `build()` is not
  read.
