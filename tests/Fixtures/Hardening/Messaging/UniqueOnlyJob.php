<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

use Illuminate\Contracts\Queue\ShouldBeUnique;

final class UniqueOnlyJob implements ShouldBeUnique {}
