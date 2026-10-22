<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Http\Request;

final class NullablePlainRequestController
{
    public function store(int $id, ?Request $request = null): void {}
}
