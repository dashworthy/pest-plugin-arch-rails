<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Notifications;

final class HelperBeforeViaNotification
{
    /** @return array<int, string> */
    public function viaQueues(): array
    {
        return ['mail' => 'default'];
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [];
    }
}
