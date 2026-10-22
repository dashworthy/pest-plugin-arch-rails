<?php

use Dashworthy\PestPluginArchRails\Http\FormRequestInspector;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\ClosureValidateController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\FormRequestController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\HelperValidateController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\InvokableController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\NullablePlainRequestController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\PlainRequestController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\PrivateHelperController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\RequestValidateController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\TraitActionController;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\ValidatorMakeController;
use PHPUnit\Framework\AssertionFailedError;

it('passes a controller whose actions take form requests or no request at all', function () {
    expect((new FormRequestInspector)(objectDescription(FormRequestController::class)))->toBeNull();
});

it('ignores a private helper that takes a plain request', function () {
    expect((new FormRequestInspector)(objectDescription(PrivateHelperController::class)))->toBeNull();
});

it('ignores validation inside a nested closure', function () {
    expect((new FormRequestInspector)(objectDescription(ClosureValidateController::class)))->toBeNull();
});

it('reports an action that takes a plain request', function (string $controller, string $parameter) {
    expect((new FormRequestInspector)(objectDescription($controller)))
        ->toContain('takes a plain Illuminate\Http\Request $'.$parameter);
})->with([
    'store' => [PlainRequestController::class, 'request'],
    'nullable' => [NullablePlainRequestController::class, 'request'],
    'invokable' => [InvokableController::class, 'request'],
]);

it('reports an action that validates inline', function (string $controller, string $call) {
    expect((new FormRequestInspector)(objectDescription($controller)))
        ->toContain('validates inline with '.$call);
})->with([
    'request validate' => [RequestValidateController::class, '->validate()'],
    'validator facade' => [ValidatorMakeController::class, 'Validator::make()'],
    'request helper' => [HelperValidateController::class, '->validate()'],
    'trait action' => [TraitActionController::class, '->validate()'],
]);

it('names the action in the failure', function () {
    expect((new FormRequestInspector)(objectDescription(ValidatorMakeController::class)))
        ->toStartWith('Expecting update() to validate through a form request');
});

it('registers as an arch expectation', function () {
    expect(FormRequestController::class)->toUseFormRequests()->ensureLazyExpectationIsVerified();

    expect(fn () => expect(PlainRequestController::class)->toUseFormRequests()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'plain Illuminate\Http\Request');
});
