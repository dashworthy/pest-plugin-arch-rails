<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Notifications;

abstract class ChannelsParentNotification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [];
    }
}
