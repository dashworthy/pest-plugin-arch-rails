<?php

use Dashworthy\PestPluginArchRails\Coverage\CoverageInspector;
use Dashworthy\PestPluginArchRails\Coverage\CoverageMap;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Coverage\App\CaseAction;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Coverage\App\DirectoryTestAction;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Coverage\App\FinalReadonlyAction;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Coverage\App\GrandchildAction;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Coverage\App\Nested\NestedCaseAction;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Coverage\App\SameNameAction;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Coverage\App\UntestedAction;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Coverage\Support\HardeningContract;
use PHPUnit\Framework\AssertionFailedError;

function hardeningCoverageFixture(string $directory): string
{
    return dirname(__DIR__).'/Fixtures/Hardening/Coverage/'.$directory;
}

/**
 * @return array<string, string>
 */
function hardeningCoverageRoots(): array
{
    return [hardeningCoverageFixture('App') => hardeningCoverageFixture('Tests')];
}

it('picks the longer of two roots that are string prefixes of each other', function () {
    $map = CoverageMap::make([
        '/app/Dom' => '/tests/Short',
        '/app/Domains' => '/tests/Long',
    ]);

    expect($map->testCandidatesFor('/app/Domains/Thing.php'))->toBe(['/tests/Long/ThingTest.php'])
        ->and($map->testCandidatesFor('/app/Dom/Thing.php'))->toBe(['/tests/Short/ThingTest.php']);
});

it('resolves dot-dot segments in a root that exists', function () {
    $map = CoverageMap::make([hardeningCoverageFixture('Support/../App') => '/tests/Unit']);

    expect($map->testCandidatesFor(hardeningCoverageFixture('App').'/CaseAction.php'))
        ->toBe(['/tests/Unit/CaseActionTest.php']);
});

it('resolves dot-dot segments in a root that does not exist', function () {
    $map = CoverageMap::make(['/nowhere/sub/../app' => '/tests/Unit']);

    expect($map->testCandidatesFor('/nowhere/app/Thing.php'))->toBe(['/tests/Unit/ThingTest.php']);
});

it('resolves a relative root against the working directory', function () {
    $relative = 'tests/Fixtures/Hardening/Coverage/App';
    $map = CoverageMap::make([$relative => '/tests/Unit']);

    expect($map->testCandidatesFor(hardeningCoverageFixture('App').'/CaseAction.php'))
        ->toBe(['/tests/Unit/CaseActionTest.php']);
});

it('tolerates several trailing separators', function () {
    $map = CoverageMap::make(['/app//' => '/tests/Unit//']);

    expect($map->testCandidatesFor('/app/Thing.php'))->toBe(['/tests/Unit/ThingTest.php']);
});

it('treats a root of the filesystem root as containing everything', function () {
    $map = CoverageMap::make(['/' => '/tests']);

    expect($map->testCandidatesFor('/app/Thing.php'))->toBe(['/tests/app/ThingTest.php']);
});

it('matches a root and a file written with Windows separators', function () {
    $map = CoverageMap::make(['C:\\app' => 'C:\\tests']);

    expect($map->testCandidatesFor('C:\\app\\Thing.php'))->not->toBe([]);
});

it('says the map has no test directory when a root maps to an empty list', function () {
    expect(fn () => CoverageMap::make([hardeningCoverageFixture('App') => []]))
        ->toThrow(InvalidArgumentException::class, 'no test directory');
});

it('passes a final readonly class whose test exists', function () {
    expect(FinalReadonlyAction::class)->toHaveTests(hardeningCoverageRoots())
        ->ensureLazyExpectationIsVerified();

    expect(true)->toBeTrue();
});

it('reports a class whose test path is a directory, not a file', function () {
    expect(fn () => expect(DirectoryTestAction::class)
        ->toHaveTests(hardeningCoverageRoots())
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'DirectoryTestActionTest.php');
});

it('reports a class whose test differs from its name only in case', function () {
    expect(fn () => expect(CaseAction::class)
        ->toHaveTests(hardeningCoverageRoots())
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'has no corresponding test');
});

it('accepts an empty suffix as a test with the same basename', function () {
    expect(SameNameAction::class)->toHaveTests(hardeningCoverageRoots(), '')
        ->ensureLazyExpectationIsVerified();

    expect(true)->toBeTrue();
});

it('exempts a class through an interface implemented by its parent', function () {
    expect(GrandchildAction::class)
        ->toHaveTests(hardeningCoverageRoots(), 'Test', null, [HardeningContract::class])
        ->ensureLazyExpectationIsVerified();

    expect(true)->toBeTrue();
});

it('does not crash on an exempt entry naming a class that does not exist', function () {
    expect(fn () => expect(UntestedAction::class)
        ->toHaveTests(hardeningCoverageRoots(), 'Test', null, ['Nope\\DoesNotExist'])
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'has no corresponding test');
});

it('rejects an unknown kind in the skip list', function () {
    expect(fn () => new CoverageInspector(CoverageMap::make(['/app' => '/tests']), ['interfaces']))
        ->toThrow(InvalidArgumentException::class, 'Unknown kind(s) [interfaces] in the coverage skip list. Valid kinds are: abstract, interface, trait, enum.');
});

it('creates no candidate for a source file outside a temp root and finds one inside it', function () {
    $root = sys_get_temp_dir().'/arch-rails-coverage-'.bin2hex(random_bytes(4));
    mkdir($root.'/app', 0777, true);
    mkdir($root.'/tests', 0777, true);
    touch($root.'/app/Thing.php');
    touch($root.'/tests/ThingTest.php');

    try {
        $map = CoverageMap::make([$root.'/app' => $root.'/tests']);
        $candidates = $map->testCandidatesFor($root.'/app/Thing.php');

        expect($candidates)->toHaveCount(1)
            ->and(is_file($candidates[0]))->toBeTrue()
            ->and($map->testCandidatesFor($root.'/application/Thing.php'))->toBe([]);
    } finally {
        unlink($root.'/app/Thing.php');
        unlink($root.'/tests/ThingTest.php');
        rmdir($root.'/app');
        rmdir($root.'/tests');
        rmdir($root);
    }
});

it('resolves a relative root containing dot-dot lexically when it does not exist', function () {
    $map = CoverageMap::make(['nowhere/./sub/../app' => '/tests/Unit']);

    expect($map->testCandidatesFor('nowhere/app/Thing.php'))->toBe(['/tests/Unit/ThingTest.php']);
});

it('reports a class whose test directory differs from its namespace only in case', function () {
    expect(fn () => expect(NestedCaseAction::class)
        ->toHaveTests(hardeningCoverageRoots())
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'has no corresponding test');
});

it('lists each test directory once per run', function () {
    $tests = sys_get_temp_dir().'/arch-rails-coverage-'.bin2hex(random_bytes(6));
    mkdir($tests);
    $inspector = new CoverageInspector(CoverageMap::make([hardeningCoverageFixture('App') => $tests]));

    touch($tests.'/FinalReadonlyActionTest.php');

    try {
        expect($inspector(objectDescription(FinalReadonlyAction::class)))->toBeNull();

        touch($tests.'/UntestedActionTest.php');

        expect($inspector(objectDescription(UntestedAction::class)))->toContain('has no corresponding test');
    } finally {
        @unlink($tests.'/UntestedActionTest.php');
        unlink($tests.'/FinalReadonlyActionTest.php');
        rmdir($tests);
    }
});
