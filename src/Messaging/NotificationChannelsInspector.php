<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Messaging;

use Dashworthy\PestPluginArchRails\Core\Inspector;
use Dashworthy\PestPluginArchRails\Core\LineFinder;
use Dashworthy\PestPluginArchRails\Core\MethodSource;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Checks that a notification's via() method, wherever it is declared, can return at least one channel.
 */
final class NotificationChannelsInspector implements Inspector
{
    private ?int $line = null;

    public function __invoke(ObjectDescription $object): ?string
    {
        $this->line = null;

        $via = MethodSource::find($object->reflectionClass, 'via');

        if (! $via instanceof MethodSource) {
            return $object->reflectionClass->hasMethod('via')
                ? null
                : 'Expecting the notification to declare its delivery channels, but it declares no via() method.';
        }

        if ($via->file === $object->reflectionClass->getFileName()) {
            $this->line = $via->line;
        }

        // An abstract via() leaves the channels to each concrete subclass, which is checked on its own.
        if ($via->node->stmts === null) {
            return null;
        }

        foreach ($via->nodesOfType(Return_::class) as $return) {
            // A computed channel list cannot be read statically; it is trusted rather than reported.
            if (! $return->expr instanceof Array_ || $return->expr->items !== []) {
                return null;
            }
        }

        return 'Expecting the notification to declare its delivery channels, but via() returns an empty channel list.';
    }

    /**
     * The via() line when it is declared in the reported file, otherwise the class line.
     */
    public function line(string $path): int
    {
        return $this->line ?? LineFinder::declaration()($path);
    }
}
