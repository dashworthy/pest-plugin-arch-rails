<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Messaging;

use Dashworthy\PestPluginArchRails\Core\Inspector;
use Dashworthy\PestPluginArchRails\Core\LineFinder;
use Dashworthy\PestPluginArchRails\Core\MethodSource;
use Illuminate\Mail\Mailables\Content;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPUnit\Architecture\Elements\ObjectDescription;
use ReflectionMethod;

/**
 * Checks that a mailable's content() method, wherever it is declared, builds its Content from a markdown template.
 *
 * Only arguments given to Content itself count: a non-null markdown: argument (or the positional argument in the
 * same place) to new Content(...), or a non-null argument to Content's markdown() method.
 */
final class MarkdownMailTemplateInspector implements Inspector
{
    private const string CONTENT = Content::class;

    private ?int $line = null;

    public function __invoke(ObjectDescription $object): ?string
    {
        $this->line = null;

        $content = MethodSource::find($object->reflectionClass, 'content');

        if (! $content instanceof MethodSource) {
            if ($object->reflectionClass->hasMethod('content')) {
                return null;
            }

            return $object->reflectionClass->hasMethod('build')
                ? 'Expecting the mailable to render from a markdown template, but it declares no content() method. build() is not read: define content() returning a Content built with markdown:.'
                : 'Expecting the mailable to render from a markdown template, but it declares no content() method.';
        }

        if ($content->file === $object->reflectionClass->getFileName()) {
            $this->line = $content->line;
        }

        foreach ($content->nodesOfType(New_::class) as $new) {
            if ($this->isContent($new) && $this->namesMarkdown($new->args)) {
                return null;
            }
        }

        foreach ($content->nodesOfType(MethodCall::class) as $call) {
            if ($call->name instanceof Identifier
                && $call->name->toLowerString() === 'markdown'
                && $this->isContentChain($call->var)
                && isset($call->args[0])
                && $call->args[0] instanceof Arg
                && ! $this->isNull($call->args[0]->value)) {
                return null;
            }
        }

        return 'Expecting the mailable to render from a markdown template, but content() does not name a markdown template. Pass markdown: to Content instead of view:.';
    }

    /**
     * The content() line when it is declared in the reported file, otherwise the class line.
     */
    public function line(string $path): int
    {
        return $this->line ?? LineFinder::methodOrClass('class ')($path);
    }

    /**
     * @param  array<Node>  $args
     */
    private function namesMarkdown(array $args): bool
    {
        $position = $this->markdownPosition();

        foreach ($args as $index => $arg) {
            if (! $arg instanceof Arg || $arg->unpack) {
                continue;
            }

            $named = $arg->name?->toString();

            if (($named === 'markdown' || ($named === null && $index === $position)) && ! $this->isNull($arg->value)) {
                return true;
            }
        }

        return false;
    }

    private function markdownPosition(): int
    {
        if (class_exists(self::CONTENT)) {
            foreach (new ReflectionMethod(self::CONTENT, '__construct')->getParameters() as $parameter) {
                if ($parameter->getName() === 'markdown') {
                    return $parameter->getPosition();
                }
            }
        }

        return 3;
    }

    private function isContent(New_ $new): bool
    {
        return $new->class instanceof Name
            && strcasecmp(ltrim($new->class->toString(), '\\'), self::CONTENT) === 0;
    }

    private function isContentChain(Expr $expr): bool
    {
        while ($expr instanceof MethodCall) {
            $expr = $expr->var;
        }

        return $expr instanceof New_ && $this->isContent($expr);
    }

    private function isNull(Expr $expr): bool
    {
        return $expr instanceof ConstFetch && $expr->name->toLowerString() === 'null';
    }
}
