<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

final class RequestValidateController
{
    public function update(ValidatingRequest $request): void
    {
        $request->validate(['name' => 'required']);
    }
}
