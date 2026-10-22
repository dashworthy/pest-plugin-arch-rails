<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Http\Request;

final class PrivateHelperController
{
    public function index(): void {}

    private function helper(Request $request): void
    {
        $request->validate([]);
    }
}
