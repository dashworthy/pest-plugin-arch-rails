<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Notifications;

final class UppercaseViaNotification
{
    /** @return array<int, string> */
    public function VIA(object $notifiable): array
    {
        return ['mail'];
    }
}
