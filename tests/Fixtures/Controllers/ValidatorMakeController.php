<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Support\Facades\Validator;

final class ValidatorMakeController
{
    public function update(int $id): void
    {
        Validator::make([], []);
    }
}
