<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

final class ValidatorHelperController
{
    public function update(): void
    {
        validator([], [])->validate();
    }
}
