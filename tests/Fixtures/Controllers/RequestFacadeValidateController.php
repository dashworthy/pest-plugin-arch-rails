<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Support\Facades\Request;

final class RequestFacadeValidateController
{
    public function update(): void
    {
        Request::validate([]);
    }
}
