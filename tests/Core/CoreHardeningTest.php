<?php

use Dashworthy\PestPluginArchRails\Core\LineFinder;
use Dashworthy\PestPluginArchRails\Core\Rule;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\AlsoFails;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\AlwaysFails;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\DocblockMentionsIt;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\NoClassToken;
use PHPUnit\Architecture\Elements\ObjectDescription;
use PHPUnit\Framework\AssertionFailedError;

/**
 * @param  class-string  $class
 */
function hardeningCorePath(string $class): string
{
    return (string) (new ReflectionClass($class))->getFileName();
}

it('propagates an exception thrown by the inspector', function () {
    expect(fn () => Rule::fromInspector(
        expect(AlwaysFails::class),
        fn (ObjectDescription $object): ?string => throw new RuntimeException('inspector broke'),
    )->ensureLazyExpectationIsVerified())->toThrow(RuntimeException::class, 'inspector broke');
});

it('still fails when the inspector returns an empty message', function () {
    expect(fn () => Rule::fromInspector(
        expect(AlwaysFails::class),
        fn (ObjectDescription $object): ?string => '',
    )->ensureLazyExpectationIsVerified())->toThrow(AssertionFailedError::class, 'Expecting this class to satisfy the rule');
});

it('does not leak one class\'s detail into the next class\'s failure', function () {
    $seen = [];

    try {
        Rule::make(
            expect([AlsoFails::class, AlwaysFails::class]),
            function (ObjectDescription $object, ?string &$detail) use (&$seen): bool {
                $seen[] = $object->name;

                if (str_ends_with($object->name, 'AlsoFails')) {
                    $detail = 'first';

                    return true;
                }

                return false;
            },
            fn ($violation, ?string $detail): string => 'detail='.var_export($detail, true),
        )->ensureLazyExpectationIsVerified();
    } catch (AssertionFailedError $failure) {
        expect($failure->getMessage())->toContain('detail=NULL');

        return;
    }

    throw new RuntimeException('expected a failure');
});

it('finds a needle on the first line', function () {
    expect(LineFinder::methodOrClass('<?php')(hardeningCorePath(AlsoFails::class)))->toBe(1);
});

it('falls back to the declaring line of an enum', function () {
    expect(LineFinder::methodOrClass('function missing')(hardeningCorePath(NoClassToken::class)))->toBe(7);
});

it('falls back to the declaration rather than a docblock mentioning class', function () {
    expect(LineFinder::methodOrClass('function missing')(hardeningCorePath(DocblockMentionsIt::class)))->toBe(10);
});

it('returns 0 for a path that does not exist', function () {
    expect(LineFinder::methodOrClass('anything')('/no/such/file.php'))->toBe(0);
});
