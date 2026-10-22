<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Notifications;

trait DeclaresChannels
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
