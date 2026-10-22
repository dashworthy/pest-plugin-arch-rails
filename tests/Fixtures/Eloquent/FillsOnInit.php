<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

trait FillsOnInit
{
    public function initializeFillsOnInit(): void
    {
        $this->mergeFillable(['name']);
    }
}
