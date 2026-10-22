<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

final class NullsafeValidateController
{
    public function update(?ValidatingRequest $request): void
    {
        $request?->validate([]);
    }
}
