<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

trait UnguardsOnInit
{
    public function initializeUnguardsOnInit(): void
    {
        $this->guarded = [];
    }
}
