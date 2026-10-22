<?php

declare(strict_types=1);

use Dashworthy\PestPluginArchRails\Core\LineFinder;
use Dashworthy\PestPluginArchRails\Core\Rule;
use Dashworthy\PestPluginArchRails\Http\FormRequestInspector;
use Dashworthy\PestPluginArchRails\Http\GateAbilityInspector;
use Dashworthy\PestPluginArchRails\Http\GateAuthorizationInspector;
use Dashworthy\PestPluginArchRails\Http\JsonResourceInspector;
use Pest\Arch\Contracts\ArchExpectation;
use Pest\Expectation;
use PHPUnit\Framework\Assert;

/*
 | Each closure MUST declare ": ArchExpectation" as its return type.
 | Pest\Expectation::__call inspects that exact return type to decide whether to
 | invoke the closure directly; without it the call is routed through
 | ExpectationPipeline and the lazy arch evaluation never runs.
 */

/*
 | $expectedAbility: Closure(string $requestClass): ?string naming the ability
 |                   the request should check, or null to accept any.
 */
expect()->extend('toAuthorizeWithGate', function (?Closure $expectedAbility = null): ArchExpectation {
    return Rule::fromInspector($this, new GateAuthorizationInspector($expectedAbility), LineFinder::methodOrClass('function authorize'));
});

/*
 | Not an arch expectation: its subject is the list of permission names, so it
 | reports every mismatch in one run.
 |
 | $directories: where to look for Gate::allows() calls.
 | $alsoChecked: abilities checked in ways source cannot show.
 */
expect()->extend('toMatchGateAbilities', function (array $directories, array $alsoChecked = []): Expectation {
    $failures = (new GateAbilityInspector($directories, $alsoChecked))((array) $this->value);

    Assert::assertTrue(
        $failures === [],
        "Expecting the permissions and the abilities the code checks to match, but:\n".implode("\n", $failures),
    );

    return $this;
});

/*
 | Every public action validates through a form request: none takes a plain
 | Illuminate\Http\Request or validates inline.
 */
expect()->extend('toUseFormRequests', function (): ArchExpectation {
    return Rule::fromInspector($this, new FormRequestInspector);
});

/*
 | Every public action declares a JsonResource subclass as its return type.
 */
expect()->extend('toReturnJsonResources', function (): ArchExpectation {
    return Rule::fromInspector($this, new JsonResourceInspector);
});
