<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ResourceController
{
    public function index(): AnonymousResourceCollection {}

    public function show(): WidgetResource {}

    public function special(): ?SpecialWidgetResource {}

    public function all(): WidgetCollection {}

    public function either(): WidgetResource|WidgetCollection {}

    public function __construct() {}

    public static function routes(): array
    {
        return [];
    }

    private function helper(): array
    {
        return [];
    }
}
