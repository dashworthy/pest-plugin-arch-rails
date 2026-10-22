<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Notifications;

final class VariableEmptyViaNotification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = [];

        return $channels;
    }
}
