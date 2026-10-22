<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Http\Request;

final class UnionPlainRequestController
{
    public function store(Request|ValidatingRequest $request): void {}
}
