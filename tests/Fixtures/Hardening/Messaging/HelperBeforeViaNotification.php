<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

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
