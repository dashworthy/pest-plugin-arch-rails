<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\StoreWidgetRequest;

final class FormRequestController
{
    public function index(): array
    {
        return [];
    }

    public function show(int $widget): int
    {
        return $widget;
    }

    public function store(StoreWidgetRequest $request): array
    {
        return $request->validated();
    }

    public function __construct() {}

    public static function routes(): void {}
}
