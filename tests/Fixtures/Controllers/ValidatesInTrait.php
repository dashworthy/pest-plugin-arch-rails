<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

trait ValidatesInTrait
{
    public function update(): void
    {
        request()->validate([]);
    }
}
