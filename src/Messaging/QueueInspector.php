<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Messaging;

use Dashworthy\PestPluginArchRails\Core\Inspector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Checks how a class is dispatched: queued, queued after commit, or deliberately synchronous.
 *
 * Only a concrete class or abstract class is dispatched, so an enum, interface or trait is reported as such rather
 * than as a class missing an interface.
 */
final readonly class QueueInspector implements Inspector
{
    private function __construct(private string $mode) {}

    public static function queued(): self
    {
        return new self('queued');
    }

    public static function afterCommit(): self
    {
        return new self('after-commit');
    }

    public static function sync(): self
    {
        return new self('sync');
    }

    public function __invoke(ObjectDescription $object): ?string
    {
        $class = $object->reflectionClass;

        $kind = match (true) {
            $class->isEnum() => 'an enum',
            $class->isInterface() => 'an interface',
            $class->isTrait() => 'a trait',
            default => null,
        };

        if ($kind !== null) {
            return sprintf('Expecting a class, but this is %s, which is never dispatched. Narrow the target to classes.', $kind);
        }

        return match ($this->mode) {
            'queued' => $class->implementsInterface(ShouldQueue::class) ? null : sprintf(
                'Expecting the class to be dispatched asynchronously, but it does not implement %s. Implement it, or assert the decision with toBeSync().',
                ShouldQueue::class,
            ),
            'after-commit' => $class->implementsInterface(ShouldQueueAfterCommit::class) ? null : sprintf(
                'Expecting the class to wait for the transaction to commit, but it does not implement %s.',
                ShouldQueueAfterCommit::class,
            ),
            default => $class->implementsInterface(ShouldQueue::class)
                ? 'Expecting the class to be deliberately synchronous, but it is queued.'
                : null,
        };
    }
}
