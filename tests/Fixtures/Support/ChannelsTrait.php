<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Support;

trait ChannelsTrait
{
    /** @return list<string> */
    public function channels(): array
    {
        return ['mail'];
    }
}
