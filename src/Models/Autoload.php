<?php

declare(strict_types=1);

use Dashworthy\PestPluginArchRails\Core\Rule;
use Dashworthy\PestPluginArchRails\Models\MassAssignmentInspector;
use Dashworthy\PestPluginArchRails\Models\TableNameInspector;
use Pest\Arch\Contracts\ArchExpectation;

/*
 | Each closure MUST declare ": ArchExpectation" as its return type.
 | Pest\Expectation::__call inspects that exact return type to decide whether to
 | invoke the closure directly; without it the call is routed through
 | ExpectationPipeline and the lazy arch evaluation never runs.
 */

expect()->extend('toMatchTableName', function (): ArchExpectation {
    return Rule::fromInspector($this, new TableNameInspector);
});

expect()->extend('toGuardMassAssignment', function (): ArchExpectation {
    return Rule::fromInspector($this, new MassAssignmentInspector);
});
