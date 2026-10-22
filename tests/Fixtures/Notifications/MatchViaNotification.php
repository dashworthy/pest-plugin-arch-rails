<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Notifications;

final class MatchViaNotification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return match (true) {
            default => ['mail'],
        };
    }
}
