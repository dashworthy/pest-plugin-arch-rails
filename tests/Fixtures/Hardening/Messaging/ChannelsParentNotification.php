<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

abstract class ChannelsParentNotification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [];
    }
}
