<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources;

final class UnionWithArrayController
{
    public function index(): WidgetResource|array {}
}
