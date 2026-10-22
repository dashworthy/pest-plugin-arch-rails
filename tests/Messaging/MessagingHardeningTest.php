<?php

use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\AbstractContentMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\AbstractViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\AssignedContentMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\ClosureReturnViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\ConditionalEmptyViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\ContentSubclassMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\DocblockFirstMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\DocblockFirstNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\FluentMarkdownMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\HelperBeforeViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\InheritsEmptyViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\InheritsQueuedJob;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\LegacyBuildMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\MatchViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\NestedScopesViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\NullMarkdownMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\PositionalMarkdownMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\PositionalNullMarkdownMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\PositionalViewMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\QueuedConcern;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\QueuedContract;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\SecondClassViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\SyncEnum;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\TernaryMarkdownMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\TraitContentMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\TraitViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\UniqueOnlyJob;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\UniqueQueuedJob;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\UnrelatedMarkdownArgMailable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\UppercaseViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\VariableEmptyViaNotification;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging\ViaContractJob;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Run a verb lazily and return the failure, or null when it passes.
 */
function messagingHardeningFailure(string $class, string $verb): ?ArchExpectationFailedException
{
    try {
        expect($class)->{$verb}()->ensureLazyExpectationIsVerified();
    } catch (ArchExpectationFailedException $e) {
        return $e;
    }

    return null;
}

// Queue verbs

it('accepts a class inheriting ShouldQueue from a parent class', function () {
    expect(InheritsQueuedJob::class)->toBeQueued();
});

it('accepts a class implementing an interface that extends ShouldQueue', function () {
    expect(ViaContractJob::class)->toBeQueued();
});

it('rejects a child of a queued parent asserted as synchronous', function () {
    expect(fn () => expect(InheritsQueuedJob::class)->toBeSync()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is queued');
});

it('treats ShouldBeUnique alone as synchronous', function () {
    expect(UniqueOnlyJob::class)->toBeSync();
});

it('accepts a unique job that also implements ShouldQueue', function () {
    expect(UniqueQueuedJob::class)->toBeQueued();
});

it('reports an enum, interface or trait given to a queue verb as not a class', function (string $target, string $verb, string $kind) {
    expect(messagingHardeningFailure($target, $verb)?->getMessage())
        ->toContain("Expecting a class, but this is {$kind}");
})->with([
    'enum, toBeQueued' => [SyncEnum::class, 'toBeQueued', 'an enum'],
    'interface, toBeQueued' => [QueuedContract::class, 'toBeQueued', 'an interface'],
    'trait, toBeQueuedAfterCommit' => [QueuedConcern::class, 'toBeQueuedAfterCommit', 'a trait'],
    'enum, toBeSync' => [SyncEnum::class, 'toBeSync', 'an enum'],
]);

// toDeclareNotificationChannels

it('accepts via() that returns an empty list in only one branch', function () {
    expect(ConditionalEmptyViaNotification::class)->toDeclareNotificationChannels();
});

it('accepts via() returning a match expression', function () {
    expect(MatchViaNotification::class)->toDeclareNotificationChannels();
});

it('accepts via() returning a variable (computed lists are trusted by design)', function () {
    expect(VariableEmptyViaNotification::class)->toDeclareNotificationChannels();
});

it('rejects via() returning [] even when a nested closure returns something', function () {
    expect(messagingHardeningFailure(ClosureReturnViaNotification::class, 'toDeclareNotificationChannels'))
        ->not->toBeNull();
});

it('does not describe an abstract via() as an empty channel list', function () {
    $failure = messagingHardeningFailure(AbstractViaNotification::class, 'toDeclareNotificationChannels');

    expect($failure)->toBeNull();
});

it('finds via() regardless of the case it is declared in', function () {
    expect(UppercaseViaNotification::class)->toDeclareNotificationChannels();
});

it('points an empty via() at the via() line, not a viaQueues() helper above it', function () {
    $failure = messagingHardeningFailure(HelperBeforeViaNotification::class, 'toDeclareNotificationChannels');

    expect($failure)->not->toBeNull()
        ->and($failure->toCollisionEditor()->getLine())->toBe(16);
});

it('accepts via() inherited from a trait', function () {
    expect(TraitViaNotification::class)->toDeclareNotificationChannels();
});

it('rejects an empty via() inherited from a parent class', function () {
    expect(messagingHardeningFailure(InheritsEmptyViaNotification::class, 'toDeclareNotificationChannels')?->getMessage())
        ->toContain('returns an empty channel list');
});

it('does not let another class in the same file supply via()', function () {
    expect(messagingHardeningFailure(SecondClassViaNotification::class, 'toDeclareNotificationChannels')?->getMessage())
        ->toContain('declares no via() method');
});

it('ignores returns inside arrow functions and anonymous classes in via()', function () {
    expect(messagingHardeningFailure(NestedScopesViaNotification::class, 'toDeclareNotificationChannels'))
        ->not->toBeNull();
});

// toUseMarkdownMailTemplates

it('accepts content() choosing markdown in one arm of a ternary', function () {
    expect(TernaryMarkdownMailable::class)->toUseMarkdownMailTemplates();
});

it('accepts a Content built into a variable and returned', function () {
    expect(AssignedContentMailable::class)->toUseMarkdownMailTemplates();
});

it('rejects content() passing markdown: null alongside view:', function () {
    expect(messagingHardeningFailure(NullMarkdownMailable::class, 'toUseMarkdownMailTemplates'))
        ->not->toBeNull();
});

it('accepts the fluent (new Content)->markdown() form', function () {
    expect(FluentMarkdownMailable::class)->toUseMarkdownMailTemplates();
});

it('reports a legacy build() mailable as needing content(), since build() is not read', function () {
    expect(fn () => expect(LegacyBuildMailable::class)->toUseMarkdownMailTemplates()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'declares no content() method. build() is not read');
});

it('accepts content() inherited from a trait', function () {
    expect(TraitContentMailable::class)->toUseMarkdownMailTemplates();
});

it('accepts a positional markdown argument to Content', function () {
    expect(PositionalMarkdownMailable::class)->toUseMarkdownMailTemplates();
});

it('rejects Content built without a non-null markdown argument', function (string $mailable) {
    expect(messagingHardeningFailure($mailable, 'toUseMarkdownMailTemplates')?->getMessage())
        ->toContain('does not name a markdown template');
})->with([
    'positional null markdown' => PositionalNullMarkdownMailable::class,
    'positional view only' => PositionalViewMailable::class,
    'markdown: passed to an unrelated call' => UnrelatedMarkdownArgMailable::class,
]);

it('accepts an abstract content(), leaving the template to each concrete subclass', function () {
    expect(messagingHardeningFailure(AbstractContentMailable::class, 'toUseMarkdownMailTemplates'))->toBeNull();
});

it('accepts a Content subclass built with markdown:', function () {
    expect(ContentSubclassMailable::class)->toUseMarkdownMailTemplates();
});

it('points an inherited empty via() at the class declaration, not a docblock above it', function () {
    $failure = messagingHardeningFailure(DocblockFirstNotification::class, 'toDeclareNotificationChannels');

    expect($failure)->not->toBeNull()
        ->and($failure->toCollisionEditor()->getLine())->toBe(10);
});

it('points a mailable with no content() at the class declaration, not a docblock above it', function () {
    $failure = messagingHardeningFailure(DocblockFirstMailable::class, 'toUseMarkdownMailTemplates');

    expect($failure)->not->toBeNull()
        ->and($failure->toCollisionEditor()->getLine())->toBe(10);
});
