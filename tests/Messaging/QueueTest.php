<?php

use Dashworthy\PestPluginArchRails\Tests\Fixtures\Queue\AfterCommitJob;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Queue\QueuedJob;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Queue\SyncJob;
use PHPUnit\Framework\AssertionFailedError;

it('accepts a class implementing ShouldQueue', function () {
    expect(QueuedJob::class)->toBeQueued();
});

it('accepts a class implementing ShouldQueueAfterCommit as queued', function () {
    expect(AfterCommitJob::class)->toBeQueued();
});

it('rejects a class implementing neither', function () {
    expect(fn () => expect(SyncJob::class)->toBeQueued()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'does not implement');
});

it('requires ShouldQueueAfterCommit specifically', function () {
    expect(AfterCommitJob::class)->toBeQueuedAfterCommit();
});

it('rejects plain ShouldQueue when after-commit is required', function () {
    expect(fn () => expect(QueuedJob::class)->toBeQueuedAfterCommit()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'does not implement');
});

it('accepts a deliberately synchronous class', function () {
    expect(SyncJob::class)->toBeSync();
});

it('rejects a queued class asserted as synchronous', function () {
    expect(fn () => expect(QueuedJob::class)->toBeSync()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is queued');
});

it('accepts a whole namespace of queued classes once the deliberate exception is excluded', function () {
    expect([AfterCommitJob::class, QueuedJob::class, SyncJob::class])
        ->classes()
        ->toBeQueued()
        ->ignoring(SyncJob::class);
});

it('rejects the same namespace without the exclusion, proving it is load-bearing', function () {
    expect(fn () => expect([AfterCommitJob::class, QueuedJob::class, SyncJob::class])
        ->classes()
        ->toBeQueued()
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'does not implement');
});
