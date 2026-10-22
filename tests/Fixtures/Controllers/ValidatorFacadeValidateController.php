<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Support\Facades\Validator;

final class ValidatorFacadeValidateController
{
    public function update(): void
    {
        Validator::validate([], []);
    }
}
