<?php

use Dashworthy\PestPluginArchRails\Http\ClassAncestry;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers\ValidatingRequest;

it('answers by reflection when both classes load', function () {
    expect(ClassAncestry::extends(LogicException::class, Exception::class))->toBeTrue()
        ->and(ClassAncestry::extends(Exception::class, LogicException::class))->toBeFalse()
        ->and(ClassAncestry::extends(InvalidArgumentException::class, '\\'.Exception::class))->toBeTrue();
});

it('answers from source when the ancestor is not installed', function () {
    expect(ClassAncestry::extends(
        ValidatingRequest::class,
        'Illuminate\Foundation\Http\FormRequest',
    ))->toBeTrue();
});
