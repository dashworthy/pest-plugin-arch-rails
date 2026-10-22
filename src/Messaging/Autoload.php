<?php

declare(strict_types=1);

use Dashworthy\PestPluginArchRails\Core\LineFinder;
use Dashworthy\PestPluginArchRails\Core\Rule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Pest\Arch\Contracts\ArchExpectation;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPUnit\Architecture\Elements\ObjectDescription;

/*
 | Each closure MUST declare ": ArchExpectation" as its return type.
 | Pest\Expectation::__call inspects that exact return type to decide whether to
 | invoke the closure directly; without it the call is routed through
 | ExpectationPipeline and the lazy arch evaluation never runs.
 */

expect()->extend('toBeQueued', function (): ArchExpectation {
    return Rule::make(
        $this,
        fn (ObjectDescription $object, ?string &$detail): bool => $object->reflectionClass->implementsInterface(ShouldQueue::class),
        fn (): string => sprintf(
            'Expecting the class to be dispatched asynchronously, but it does not implement %s. Implement it, or assert the decision with toBeSync().',
            ShouldQueue::class,
        ),
    );
});

expect()->extend('toBeQueuedAfterCommit', function (): ArchExpectation {
    return Rule::make(
        $this,
        fn (ObjectDescription $object, ?string &$detail): bool => $object->reflectionClass->implementsInterface(ShouldQueueAfterCommit::class),
        fn (): string => sprintf(
            'Expecting the class to wait for the transaction to commit, but it does not implement %s.',
            ShouldQueueAfterCommit::class,
        ),
    );
});

expect()->extend('toBeSync', function (): ArchExpectation {
    return Rule::make(
        $this,
        fn (ObjectDescription $object, ?string &$detail): bool => ! $object->reflectionClass->implementsInterface(ShouldQueue::class),
        fn (): string => 'Expecting the class to be deliberately synchronous, but it is queued.',
    );
});

expect()->extend('toDeclareNotificationChannels', function (): ArchExpectation {
    return Rule::make(
        $this,
        function (ObjectDescription $object, ?string &$detail): bool {
            $finder = new NodeFinder;

            /** @var array<int, ClassMethod> $methods */
            $methods = $finder->findInstanceOf($object->stmts, ClassMethod::class);

            $via = null;

            foreach ($methods as $method) {
                if ($method->name->toString() === 'via') {
                    $via = $method;

                    break;
                }
            }

            if (! $via instanceof ClassMethod) {
                $detail = 'no-via';

                return false;
            }

            /** @var array<int, Return_> $returns */
            $returns = $finder->findInstanceOf($via->stmts ?? [], Return_::class);

            foreach ($returns as $return) {
                if (! $return->expr instanceof Array_) {
                    // A computed channel list cannot be read statically; treat
                    // it as declared rather than reporting correct code.
                    return true;
                }

                if ($return->expr->items !== []) {
                    return true;
                }
            }

            $detail = 'empty';

            return false;
        },
        fn ($violation, ?string $detail): string => $detail === 'no-via'
            ? 'Expecting the notification to declare its delivery channels, but it declares no via() method.'
            : 'Expecting the notification to declare its delivery channels, but via() returns an empty channel list.',
        LineFinder::methodOrClass('function via'),
    );
});

expect()->extend('toUseMarkdownMailTemplates', function (): ArchExpectation {
    return Rule::make(
        $this,
        function (ObjectDescription $object, ?string &$detail): bool {
            $finder = new NodeFinder;

            /** @var array<int, ClassMethod> $methods */
            $methods = $finder->findInstanceOf($object->stmts, ClassMethod::class);

            $content = null;

            foreach ($methods as $method) {
                if ($method->name->toString() === 'content') {
                    $content = $method;

                    break;
                }
            }

            if (! $content instanceof ClassMethod) {
                $detail = 'no-content';

                return false;
            }

            /** @var array<int, Arg> $args */
            $args = $finder->findInstanceOf($content->stmts ?? [], Arg::class);

            foreach ($args as $arg) {
                if ($arg->name?->toString() === 'markdown') {
                    return true;
                }
            }

            $detail = 'no-markdown';

            return false;
        },
        fn ($violation, ?string $detail): string => $detail === 'no-content'
            ? 'Expecting the mailable to render from a markdown template, but it declares no content() method.'
            : 'Expecting the mailable to render from a markdown template, but content() does not name a markdown template. Pass markdown: to Content instead of view:.',
        LineFinder::methodOrClass('function content'),
    );
});
