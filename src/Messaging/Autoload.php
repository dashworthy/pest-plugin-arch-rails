<?php

declare(strict_types=1);

use Dashworthy\PestPluginArchRails\Core\Rule;
use Dashworthy\PestPluginArchRails\Messaging\MarkdownMailTemplateInspector;
use Dashworthy\PestPluginArchRails\Messaging\NotificationChannelsInspector;
use Dashworthy\PestPluginArchRails\Messaging\QueueInspector;
use Pest\Arch\Contracts\ArchExpectation;

/*
 | Each closure MUST declare ": ArchExpectation" as its return type.
 | Pest\Expectation::__call inspects that exact return type to decide whether to
 | invoke the closure directly; without it the call is routed through
 | ExpectationPipeline and the lazy arch evaluation never runs.
 */

expect()->extend('toBeQueued', function (): ArchExpectation {
    return Rule::fromInspector($this, QueueInspector::queued());
});

expect()->extend('toBeQueuedAfterCommit', function (): ArchExpectation {
    return Rule::fromInspector($this, QueueInspector::afterCommit());
});

expect()->extend('toBeSync', function (): ArchExpectation {
    return Rule::fromInspector($this, QueueInspector::sync());
});

expect()->extend('toDeclareNotificationChannels', function (): ArchExpectation {
    $inspector = new NotificationChannelsInspector;

    return Rule::fromInspector($this, $inspector, $inspector->line(...));
});

expect()->extend('toUseMarkdownMailTemplates', function (): ArchExpectation {
    $inspector = new MarkdownMailTemplateInspector;

    return Rule::fromInspector($this, $inspector, $inspector->line(...));
});
