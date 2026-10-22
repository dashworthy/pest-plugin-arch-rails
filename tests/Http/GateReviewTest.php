<?php

use Dashworthy\PestPluginArchRails\Http\GateAbilities;
use Dashworthy\PestPluginArchRails\Http\GateAuthorizationInspector;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\AuthorizeAfterReturnRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\AuthorizeLineRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\ClosureReturnRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\ConstantRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\EnumValueRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\ForUserRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\GuardFalseRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\NegatedAndRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\OrRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\OrTrueTernaryRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\TernaryRequest;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review\UnresolvableRequest;
use Pest\Arch\Exceptions\ArchExpectationFailedException;

describe('policy checks', function () {
    it('leaves a check with a model out of permission matching', function () {
        expect(GateAbilities::inCode('$user->can("update", $post); Gate::allows("view", $post); $route->middleware("can:update,post"); $this->authorize("edit", $post);'))
            ->toBe([]);
    });

    it('leaves a Blade policy check out of permission matching', function () {
        expect(GateAbilities::inBlade("@can('view', \$post) @endcan @canany(['a', 'b'], \$post) @endcanany"))->toBe([]);
    });
});

describe('check forms', function () {
    it('reads every way of checking an ability', function () {
        $code = <<<'PHP'
            $this->authorize('controller_authorize');
            $this->authorizeForUser($user, 'for_user_authorize');
            Gate::forUser($user)->allows('for_user_allows');
            Gate::forUser($user)->denies('for_user_denies');
            $user->canAny(['can_any_one', 'can_any_two']);
            Authorize::using('middleware_using');
            #[Authorize('attribute_ability')]
            class Controller {}
            PHP;

        expect(GateAbilities::inCode($code))->toEqualCanonicalizing([
            'controller_authorize', 'for_user_authorize', 'for_user_allows', 'for_user_denies', 'can_any_one',
            'can_any_two', 'middleware_using', 'attribute_ability',
        ]);
    });

    it('does not crash on a first-class callable check', function () {
        expect(GateAbilities::inCode('$check = $user->can(...); $gate = Gate::allows(...);'))->toBe([]);
    });

    it('resolves an enum case value and a class constant', function () {
        $read = fn (string $class): array => GateAbilities::inCode((string) file_get_contents((string) (new ReflectionClass($class))->getFileName()));

        expect($read(EnumValueRequest::class))->toBe(['widgets_update'])
            ->and($read(ConstantRequest::class))->toBe(['widgets_store']);
    });
});

describe('parsing files as they are', function () {
    it('reads a check after inline HTML, an echo tag, an upper-case tag, a shebang or a BOM', function (string $code) {
        expect(GateAbilities::inCode($code))->toBe(['found']);
    })->with([
        'inline html' => "<html><?php Gate::allows('found'); ?></html>",
        'echo tag' => "<p><?= Gate::allows('found') ?></p>",
        'upper case tag' => "<?PHP Gate::allows('found');",
        'shebang' => "#!/usr/bin/env php\n<?php Gate::allows('found');",
        'bom' => "\xEF\xBB\xBF<?php Gate::allows('found');",
    ]);
});

describe('Blade', function () {
    it('ignores Blade comments', function () {
        expect(GateAbilities::inBlade("{{-- @can('commented') --}}\n{{--\n@cannot('multiline')\n--}}@can('real')"))->toBe(['real']);
    });

    it('reads @elsecanany', function () {
        expect(GateAbilities::inBlade("@elsecanany(['else_one', 'else_two'])"))->toBe(['else_one', 'else_two']);
    });

    it('skips an interpolated ability', function () {
        expect(GateAbilities::inBlade('@can("edit-{$type}") @can("edit-$type") @can("plain")'))->toBe(['plain']);
    });
});

describe('toAuthorizeWithGate', function () {
    it('passes a request whose check decides every return', function (string $request) {
        expect((new GateAuthorizationInspector)(objectDescription($request)))->toBeNull();
    })->with([
        'closures with their own returns' => ClosureReturnRequest::class,
        'two checks joined with ||' => OrRequest::class,
        'a ternary of checks' => TernaryRequest::class,
        'a guard returning false' => GuardFalseRequest::class,
        'Gate::forUser()->allows()' => ForUserRequest::class,
        'an enum ->value ability' => EnumValueRequest::class,
        'an unresolvable ability without an expectation' => UnresolvableRequest::class,
    ]);

    it('reports a request a return can get past', function (string $request) {
        expect((new GateAuthorizationInspector)(objectDescription($request)))->toContain('does not decide what it returns');
    })->with([
        'a return before Gate::authorize()' => AuthorizeAfterReturnRequest::class,
        'a negated &&' => NegatedAndRequest::class,
        'a ternary with a true branch' => OrTrueTernaryRequest::class,
    ]);

    it('resolves an enum ->value and a class constant against the expected ability', function () {
        expect((new GateAuthorizationInspector(fn (): string => 'widgets_update'))(objectDescription(EnumValueRequest::class)))->toBeNull()
            ->and((new GateAuthorizationInspector(fn (): string => 'widgets_store'))(objectDescription(ConstantRequest::class)))->toBeNull();
    });

    it('reports an expected ability it cannot read from source', function () {
        expect((new GateAuthorizationInspector(fn (): string => 'widgets_store'))(objectDescription(UnresolvableRequest::class)))
            ->toContain('cannot be read from source');
    });

    it('points a failure at authorize(), not a method whose name starts with it', function () {
        try {
            expect(AuthorizeLineRequest::class)->toAuthorizeWithGate()->ensureLazyExpectationIsVerified();
        } catch (ArchExpectationFailedException $e) {
            $line = $e->toCollisionEditor()->getLine();
        }

        expect($line ?? null)->toBe(12);
    });
});
