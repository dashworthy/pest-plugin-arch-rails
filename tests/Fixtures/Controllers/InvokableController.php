<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Http\Request;

final class InvokableController
{
    public function __invoke(Request $request): void {}
}
