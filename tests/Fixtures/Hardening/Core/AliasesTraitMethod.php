<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Core;

final class AliasesTraitMethod
{
    use ChannelsTrait {
        channels as via;
    }
}
