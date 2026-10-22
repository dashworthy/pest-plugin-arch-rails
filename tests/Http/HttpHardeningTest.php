<?php

use Dashworthy\PestPluginArchRails\Http\GateAbilities;
use Dashworthy\PestPluginArchRails\Http\GateAbilityInspector;
use Dashworthy\PestPluginArchRails\Http\GateAuthorizationInspector;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\AliasedGateRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\AndCombinedRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\ApostropheAbilityRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\AuthorizeThenTrueRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\BypassedWithOrTrueRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\CommentedOutGateRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\DeniesGateRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\DottedAbilityRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\DoubleQuotedGateRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\EarlyReturnTrueRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\FullyQualifiedGateRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\InspectAllowedRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\LookalikeGateRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\MultilineGateRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\ReturnsTrueAfterGateRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\TraitAuthorizedRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\UserCanRequest;
use PHPUnit\Framework\ExpectationFailedException;

function hardeningAbilities(): array
{
    return [dirname(__DIR__).'/Fixtures/Hardening/Http/Abilities'];
}

it('passes a request that calls Gate through its fully qualified name', function () {
    expect((new GateAuthorizationInspector)(objectDescription(FullyQualifiedGateRequest::class)))->toBeNull();
});

it('passes a request that quotes its ability with double quotes', function () {
    $inspect = new GateAuthorizationInspector(fn (string $request): string => 'widgets_store');

    expect($inspect(objectDescription(DoubleQuotedGateRequest::class)))->toBeNull();
});

it('passes a request that calls Gate through an aliased import', function () {
    expect((new GateAuthorizationInspector)(objectDescription(AliasedGateRequest::class)))->toBeNull();
});

it('passes a request whose Gate call has a comment before the ability', function () {
    expect((new GateAuthorizationInspector)(objectDescription(MultilineGateRequest::class)))->toBeNull();
});

it('passes a request whose authorize() comes from a trait', function () {
    expect((new GateAuthorizationInspector)(objectDescription(TraitAuthorizedRequest::class)))->toBeNull();
});

it('passes an ability containing dots and colons', function () {
    $inspect = new GateAuthorizationInspector(fn (string $request): string => 'admin:widgets.store');

    expect($inspect(objectDescription(DottedAbilityRequest::class)))->toBeNull();
});

it('reads an ability containing an apostrophe in full', function () {
    $inspect = new GateAuthorizationInspector(fn (string $request): string => "widget's_store");

    expect($inspect(objectDescription(ApostropheAbilityRequest::class)))->toBeNull();
});

it('reports a request whose Gate check is bypassed with || true', function () {
    expect((new GateAuthorizationInspector)(objectDescription(BypassedWithOrTrueRequest::class)))->not->toBeNull();
});

it('reports a request that discards its Gate check and returns true', function () {
    expect((new GateAuthorizationInspector)(objectDescription(ReturnsTrueAfterGateRequest::class)))->not->toBeNull();
});

it('reports a request that calls allows() on a class that only ends in Gate', function () {
    expect((new GateAuthorizationInspector)(objectDescription(LookalikeGateRequest::class)))
        ->toContain('authorize() does not call Gate::allows()');
});

it('does not read a commented-out Gate call as a check', function () {
    expect((new GateAuthorizationInspector)(objectDescription(CommentedOutGateRequest::class)))
        ->toContain('authorize() does not call Gate::allows()');
});

it('reads a negated Gate::denies()', function () {
    expect((new GateAuthorizationInspector)(objectDescription(DeniesGateRequest::class)))->toBeNull();
});

it('passes a request that guards with Gate::authorize() and then returns true', function () {
    expect((new GateAuthorizationInspector(fn (string $request): string => 'widgets_store'))(objectDescription(AuthorizeThenTrueRequest::class)))->toBeNull();
});

it('passes a request that joins its check with &&', function () {
    expect((new GateAuthorizationInspector)(objectDescription(AndCombinedRequest::class)))->toBeNull();
});

it('passes a request that checks $this->user()->can()', function () {
    expect((new GateAuthorizationInspector(fn (string $request): string => 'widgets_store'))(objectDescription(UserCanRequest::class)))->toBeNull();
});

it('passes a request that returns Gate::inspect()->allowed()', function () {
    expect((new GateAuthorizationInspector)(objectDescription(InspectAllowedRequest::class)))->toBeNull();
});

it('reports a request with an early return true that skips the check', function () {
    expect((new GateAuthorizationInspector)(objectDescription(EarlyReturnTrueRequest::class)))->not->toBeNull();
});

it('reads every supported check form and ignores comments and lookalikes', function () {
    expect(GateAbilities::inDirectories([dirname(__DIR__).'/Fixtures/Hardening/Http/Forms']))->toEqualCanonicalizing([
        'denies_ability', 'check_ability', 'authorize_ability', 'inspect_ability', 'any_one', 'any_two', 'none_ability',
        'fqcn_ability', 'user_can', 'user_cannot', 'auth_user_can', 'this_user_can', 'middleware_ability', 'route_can',
        'blade_can', 'blade_cannot', 'blade_any_one', 'blade_any_two', 'blade_gate',
    ]);
});

it('reports an empty expected ability rather than accepting it', function () {
    $inspect = new GateAuthorizationInspector(fn (string $request): string => '');

    expect($inspect(objectDescription(DoubleQuotedGateRequest::class)))->toContain("check the '' permission");
});

it('reads abilities with dots and colons from nested directories', function () {
    $inspect = new GateAbilityInspector(hardeningAbilities());

    expect($inspect(['posts.update', 'admin:read', 'vendored_ability']))->toBe([]);
});

it('scans a vendored directory but skips a vendor directory and non-php files', function () {
    $inspect = new GateAbilityInspector(hardeningAbilities());

    expect($inspect(['posts.update', 'admin:read']))->toBe([
        "Gate::allows() checks the ability 'vendored_ability', but no such permission exists, so it can never be granted. Add the permission, or fix the ability name.",
    ]);
});

it('passes an empty permission list against a directory with no checks', function () {
    $inspect = new GateAbilityInspector([dirname(__DIR__).'/Fixtures/Hardening/Http/Abilities/vendor']);

    expect($inspect([]))->toBe([]);
});

it('reports an unused duplicated permission once', function () {
    $inspect = new GateAbilityInspector(hardeningAbilities());

    expect($inspect(['posts.update', 'admin:read', 'vendored_ability', 'ghost', 'ghost']))->toHaveCount(1);
});

it('fails the expectation with a readable message for a directory that does not exist', function () {
    expect(fn () => expect(['posts.update'])->toMatchGateAbilities([dirname(__DIR__).'/Fixtures/Hardening/Http/Missing']))
        ->toThrow(ExpectationFailedException::class);
});

it('does not let alsoChecked hide an ability no permission backs', function () {
    $inspect = new GateAbilityInspector(hardeningAbilities(), ['vendored_ability']);

    expect($inspect(['posts.update', 'admin:read']))->toHaveCount(1);
});
