<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Core;

trait ChannelsTrait
{
    /** @return list<string> */
    public function channels(): array
    {
        return ['mail'];
    }
}
