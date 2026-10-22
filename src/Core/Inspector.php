<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Core;

use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Decides whether one class satisfies an architecture rule.
 */
interface Inspector
{
    /**
     * The failure message for the class, or null when it satisfies the rule.
     */
    public function __invoke(ObjectDescription $object): ?string;
}
