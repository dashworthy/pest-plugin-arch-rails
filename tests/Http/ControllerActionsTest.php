<?php

require_once __DIR__.'/../Fixtures/Stubs/framework.php';

use Dashworthy\PestPluginArchRails\Http\ControllerActions;
use Dashworthy\PestPluginArchRails\Http\FormRequestInspector;
use Dashworthy\PestPluginArchRails\Http\JsonResourceInspector;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\FrameworkController;

it('leaves out methods from the framework controller and its Illuminate traits', function () {
    $actions = array_map(
        fn (ReflectionMethod $method): string => $method->getName(),
        ControllerActions::of(new ReflectionClass(FrameworkController::class)),
    );

    expect($actions)->toBe(['store']);
});

it('passes a compliant controller that uses the framework traits', function () {
    expect((new FormRequestInspector)(objectDescription(FrameworkController::class)))->toBeNull()
        ->and((new JsonResourceInspector)(objectDescription(FrameworkController::class)))->toBeNull();
});
