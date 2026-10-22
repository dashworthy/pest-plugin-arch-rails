<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

final class ConditionalEmptyViaNotification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof \stdClass) {
            return [];
        }

        return ['mail'];
    }
}
