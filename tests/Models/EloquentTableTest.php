<?php

use Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent\ConventionalModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent\FillableModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent\GuardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent\LegacyTableModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent\NotAModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent\WideOpenModel;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\AssertionFailedError;

it('accepts a model whose table follows the convention', function () {
    expect(ConventionalModel::class)->toMatchTableName();
});

it('names both the resolved and the expected table', function () {
    expect(fn () => expect(LegacyTableModel::class)->toMatchTableName()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, "resolves table 'tbl_legacy_widgets', expected 'legacy_table_models'");
});

it('reports a non-model as a selection mistake', function () {
    expect(fn () => expect(NotAModel::class)->toMatchTableName()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is not an Eloquent model');
});

it('accepts a whole namespace of models once the legacy table is excluded', function () {
    expect([ConventionalModel::class, FillableModel::class, GuardedModel::class, LegacyTableModel::class, NotAModel::class, WideOpenModel::class])
        ->classes()
        ->extending(Model::class)
        ->toMatchTableName()
        ->ignoring(LegacyTableModel::class);
});

it('rejects the same namespace without the exclusion, proving it is load-bearing', function () {
    expect(fn () => expect([ConventionalModel::class, FillableModel::class, GuardedModel::class, LegacyTableModel::class, NotAModel::class, WideOpenModel::class])
        ->classes()
        ->extending(Model::class)
        ->toMatchTableName()
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, "resolves table 'tbl_legacy_widgets'");
});

/*
 | The type filter is what keeps NotAModel out of the selection. Without it
 | the same sweep reports a class that was never meant to be a model, which
 | is exactly what the not-an-Eloquent-model message tells you to fix.
 */
it('sweeps a non-model into the selection when the type filter is dropped', function () {
    expect(fn () => expect([ConventionalModel::class, FillableModel::class, GuardedModel::class, LegacyTableModel::class, NotAModel::class, WideOpenModel::class])
        ->classes()
        ->toMatchTableName()
        ->ignoring(LegacyTableModel::class)
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is not an Eloquent model');
});
