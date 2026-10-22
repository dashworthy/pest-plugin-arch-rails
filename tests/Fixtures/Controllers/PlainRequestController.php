<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Http\Request;

final class PlainRequestController
{
    public function store(Request $request): void {}
}
