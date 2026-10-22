<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\StoreWidgetRequest;

final class RequestValidateController
{
    public function update(StoreWidgetRequest $request): void
    {
        $request->validate(['name' => 'required']);
    }
}
