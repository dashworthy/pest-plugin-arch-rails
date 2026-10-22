<?php

use Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent\GuardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\AttributeFillableModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\AttributeGuardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\AttributeUnguardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\ConfigTableModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\EmptyBothModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\FakeModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\HTTPLog;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\InheritsAttributeGuardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\InheritsGuardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\InheritsUnguardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\ModelLikeEnum;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\ModelLikeInterface;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\ModelLikeTrait;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\OverriddenTableMethodModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\Person;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\PluralPivotUser;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\RequiredArgsModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\RoleUser;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\SchemaPrefixedWidget;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\StarGuardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\TableAttributeLegacyModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\TableAttributeModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\Taggable;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\TagUser;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\ThrowingConstructorModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\ThrowingInitializerModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\TraitFillableModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\TraitUnguardedModel;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models\UnguardedBase;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\AssertionFailedError;

it('accepts an irregular plural table name', function () {
    expect(Person::class)->toMatchTableName();
});

it('accepts an acronym class name using Eloquent\'s own derivation', function () {
    expect(HTTPLog::class)->toMatchTableName();
});

it('honours a getTable() method override', function () {
    expect(fn () => expect(OverriddenTableMethodModel::class)->toMatchTableName()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, "resolves table 'legacy_things', expected 'overridden_table_method_models'");
});

it('accepts a conventional table qualified with a schema prefix', function () {
    expect(SchemaPrefixedWidget::class)->toMatchTableName();
});

it('accepts a pivot relying on Eloquent\'s default (singular) pivot table name', function () {
    expect(RoleUser::class)->toMatchTableName();
});

it('accepts a morph pivot relying on Eloquent\'s default table name', function () {
    expect(Taggable::class)->toMatchTableName();
});

it('accepts a pivot that declares a fillable list', function () {
    expect(RoleUser::class)->toGuardMassAssignment();
    expect(Taggable::class)->toGuardMassAssignment();
});

it('does not run a constructor that throws', function () {
    expect(ThrowingConstructorModel::class)->toGuardMassAssignment()->toMatchTableName();
});

it('handles a model with required constructor arguments', function () {
    expect(RequiredArgsModel::class)->toGuardMassAssignment()->toMatchTableName();
});

it('accepts $guarded = [\'*\']', function () {
    expect(StarGuardedModel::class)->toGuardMassAssignment();
});

it('rejects an empty $fillable alongside an empty $guarded', function () {
    expect(fn () => expect(EmptyBothModel::class)->toGuardMassAssignment()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'declares an empty $guarded with no $fillable allow-list');
});

it('rejects a model that inherits an empty $guarded from its parent', function () {
    expect(fn () => expect(InheritsUnguardedModel::class)->toGuardMassAssignment()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'declares an empty $guarded');
});

it('accepts a model that inherits a guard list from its parent', function () {
    expect(InheritsGuardedModel::class)->toGuardMassAssignment();
});

it('passes the abstract unguarded base itself (by design)', function () {
    expect(UnguardedBase::class)->toGuardMassAssignment()->toMatchTableName();
});

it('is not affected by a global Model::unguard() left on by other code', function () {
    Model::unguard();

    try {
        expect(GuardedModel::class)->toGuardMassAssignment()->ensureLazyExpectationIsVerified();
    } finally {
        Model::reguard();
    }
});

it('reports interfaces, traits and enums as non-models', function (string $class) {
    expect(fn () => expect($class)->toMatchTableName()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is not an Eloquent model');
    expect(fn () => expect($class)->toGuardMassAssignment()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is not one');
})->with([ModelLikeInterface::class, ModelLikeTrait::class, ModelLikeEnum::class]);

it('reports a look-alike class with $guarded = [] as a non-model, not as unguarded', function () {
    expect(fn () => expect(FakeModel::class)->toGuardMassAssignment()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is not one');
});

it('reports a getTable() that needs the container as an assertion failure, not a crash', function () {
    expect(fn () => expect(ConfigTableModel::class)->toMatchTableName()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, "Expecting the model's table name to be resolvable, but resolving it threw");
});

it('accepts a model whose allow-list is declared with #[Fillable]', function () {
    expect(AttributeFillableModel::class)->toGuardMassAssignment();
});

it('accepts a model guarded with #[Guarded], directly or from a parent', function () {
    expect(AttributeGuardedModel::class)->toGuardMassAssignment();
    expect(InheritsAttributeGuardedModel::class)->toGuardMassAssignment();
});

it('rejects a model marked #[Unguarded]', function () {
    expect(fn () => expect(AttributeUnguardedModel::class)->toGuardMassAssignment()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'declares an empty $guarded');
});

it('accepts a #[Table] attribute naming the conventional table', function () {
    expect(TableAttributeModel::class)->toMatchTableName();
});

it('rejects a #[Table] attribute naming a custom table', function () {
    expect(fn () => expect(TableAttributeLegacyModel::class)->toMatchTableName()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, "resolves table 'legacy_widgets', expected 'table_attribute_legacy_models'");
});

it('rejects a pivot whose table is the plural rather than Eloquent\'s singular derivation', function () {
    expect(fn () => expect(PluralPivotUser::class)->toMatchTableName()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, "expected 'plural_pivot_user'");
});

it('runs trait initializers, catching one that unguards the model', function () {
    expect(fn () => expect(TraitUnguardedModel::class)->toGuardMassAssignment())
        ->toThrow(AssertionFailedError::class);
});

it('runs trait initializers, accepting one that merges a fillable allow-list', function () {
    expect(TraitFillableModel::class)->toGuardMassAssignment();
});

it('reports a throwing trait initializer as a violation instead of crashing', function () {
    expect(fn () => expect(ThrowingInitializerModel::class)->toGuardMassAssignment())
        ->toThrow(AssertionFailedError::class, 'initializeThrowsOnInit');
});

it('treats a model using the AsPivot trait as a pivot with a singular table', function () {
    expect(TagUser::class)->toMatchTableName();
});
