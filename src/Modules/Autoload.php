<?php

declare(strict_types=1);

use Dashworthy\PestPluginArchRails\Core\Rule;
use Dashworthy\PestPluginArchRails\Modules\ApprovedDirectoriesInspector;
use Dashworthy\PestPluginArchRails\Modules\DirectorySuffixesInspector;
use Pest\Arch\Contracts\ArchExpectation;

/*
 | Each closure MUST declare ": ArchExpectation" as its return type.
 | Pest\Expectation::__call inspects that exact return type to decide whether to
 | invoke the closure directly; without it the call is routed through
 | ExpectationPipeline and the lazy arch evaluation never runs.
 */

/*
 | $directories: the approved names for the directories directly beneath
 |               $beneath. A name given as a key, such as
 |               'Grids' => AbstractGrid::class, names the class or interface
 |               every class in that directory must extend or implement. The
 |               value may instead be a Closure(class-string $class): bool|string
 |               given each class there, returning true to accept it, or false
 |               or the reason to reject it.
 | $beneath:     the parent namespace, * matching any one segment, such as
 |               'App\Domains\*\*' for every module two levels beneath App\Domains.
 */
expect()->extend('toUseApprovedDirectories', function (array $directories, string $beneath): ArchExpectation {
    return Rule::fromInspector($this, new ApprovedDirectoriesInspector($directories, $beneath));
});

/*
 | $suffixes: the suffix every class in a directory directly beneath $beneath
 |            ends in, keyed by directory name, such as 'Actions' => 'Action'.
 |            A directory not listed is not checked.
 | $beneath:  the parent namespace, * matching any one segment, as for
 |            toUseApprovedDirectories().
 */
expect()->extend('toUseDirectorySuffixes', function (array $suffixes, string $beneath): ArchExpectation {
    return Rule::fromInspector($this, new DirectorySuffixesInspector($suffixes, $beneath));
});
