<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Notifications;

final class NestedScopesViaNotification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $arrow = fn (): array => ['mail'];
        $object = new class
        {
            /** @return array<int, string> */
            public function channels(): array
            {
                return ['mail'];
            }
        };

        return [];
    }
}
