<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

final class ClosureReturnViaNotification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $unused = array_map(function (string $channel): string {
            return $channel;
        }, ['mail']);

        return [];
    }
}
