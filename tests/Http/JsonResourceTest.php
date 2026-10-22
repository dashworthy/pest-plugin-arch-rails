<?php

use Dashworthy\PestPluginArchRails\Http\JsonResourceInspector;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources\ArrayController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources\JsonResponseController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources\PlainClassController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources\ResourceController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources\UnionWithArrayController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources\UntypedController;
use PHPUnit\Framework\AssertionFailedError;

it('passes actions returning resources, collections, subclasses, nullable and union resources', function () {
    expect((new JsonResourceInspector)(objectDescription(ResourceController::class)))->toBeNull();
});

it('reports an action returning something other than a resource', function (string $controller, string $declared) {
    expect((new JsonResourceInspector)(objectDescription($controller)))
        ->toBe("Expecting index() to return an API resource, but it declares {$declared}. Return a Illuminate\\Http\\Resources\\Json\\JsonResource subclass instead.");
})->with([
    'array' => [ArrayController::class, 'array'],
    'json response' => [JsonResponseController::class, 'Illuminate\Http\JsonResponse'],
    'plain class' => [PlainClassController::class, 'Dashworthy\PestPluginArchRails\Tests\Fixtures\Resources\NotAResource'],
    'union with array' => [UnionWithArrayController::class, 'array'],
]);

it('reports an action that declares no return type', function () {
    expect((new JsonResourceInspector)(objectDescription(UntypedController::class)))
        ->toContain('declares no return type');
});

it('registers as an arch expectation', function () {
    expect(ResourceController::class)->toReturnJsonResources()->ensureLazyExpectationIsVerified();

    expect(fn () => expect(ArrayController::class)->toReturnJsonResources()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'but it declares array');
});
