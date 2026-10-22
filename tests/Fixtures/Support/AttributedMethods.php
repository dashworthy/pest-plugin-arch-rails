<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Support;

final class AttributedMethods
{
    #[\Override]
    public function __toString(): string
    {
        return 'one';
    }

    #[\Deprecated]
    #[\NoDiscard]
    public function many(): string
    {
        return 'many';
    }
}
