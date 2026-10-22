<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Notifications;

abstract class AbstractViaNotification
{
    /** @return array<int, string> */
    abstract public function via(object $notifiable): array;
}
