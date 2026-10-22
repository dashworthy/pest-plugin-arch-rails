<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;

abstract class QueuedParentJob implements ShouldQueue {}
