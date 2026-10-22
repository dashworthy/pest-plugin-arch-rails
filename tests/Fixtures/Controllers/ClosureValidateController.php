<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\StoreWidgetRequest;

final class ClosureValidateController
{
    public function update(StoreWidgetRequest $request): void
    {
        $later = fn () => request()->validate([]);
    }
}
