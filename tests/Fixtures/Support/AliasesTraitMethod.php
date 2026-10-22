<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Support;

final class AliasesTraitMethod
{
    use ChannelsTrait {
        channels as via;
    }
}
