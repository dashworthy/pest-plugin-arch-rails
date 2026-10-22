<?php

use Dashworthy\PestPluginArchRails\Http\GateAbilities;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\StoreWidgetRequest;

it('reads the abilities checked with a string literal', function () {
    expect(GateAbilities::inCode("Gate::allows('widgets_index'); Gate::allows( \"widgets_show\" );"))
        ->toBe(['widgets_index', 'widgets_show']);
});

it('ignores an ability built at runtime', function () {
    expect(GateAbilities::inCode('Gate::allows($ability); Gate::allows(Permission::INDEX);'))->toBe([]);
});

it('lists an ability checked twice once', function () {
    expect(GateAbilities::inCode("Gate::allows('widgets_index') || Gate::allows('widgets_index')"))->toBe(['widgets_index']);
});

it('reads the abilities authorize() decides on', function () {
    expect(GateAbilities::decidingMethod(new ReflectionMethod(StoreWidgetRequest::class, 'authorize')))
        ->toBe(['abilities' => ['widgets_store'], 'unresolved' => false]);
});

it('reads every php file beneath the directories', function () {
    $directory = dirname(__DIR__).'/Fixtures/Gate';

    expect(GateAbilities::inDirectories([$directory]))->toEqualCanonicalizing(['widgets_index', 'widgets_show', 'gadgets_index']);
});
