<?php

use Dashworthy\PestPluginArchRails\Modules\ApprovedDirectoriesInspector;
use Dashworthy\PestPluginArchRails\Modules\DirectorySuffixesInspector;
use Dashworthy\PestPluginArchRails\Modules\ModulePattern;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\OrderContract;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders\Actions\Action;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders\Actions\ActionAction;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders\Actions\Deep\Er\Still\ShipOrder;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders\Actions\PlaceOrder;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders\Enums\OrderState;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders\OrderHelper;

const HARDENING_MODULES = 'Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\*\*';

describe('ModulePattern', function () {
    it('ignores leading and trailing backslashes in the pattern', function (string $pattern) {
        expect((new ModulePattern($pattern))->locate(PlaceOrder::class))
            ->toBe(['module' => 'Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders', 'directory' => 'Actions']);
    })->with([
        'leading' => '\\'.HARDENING_MODULES,
        'trailing' => HARDENING_MODULES.'\\',
        'both' => '\\'.HARDENING_MODULES.'\\',
    ]);

    it('ignores a leading backslash on the class name', function () {
        expect((new ModulePattern(HARDENING_MODULES))->locate('\\'.PlaceOrder::class))
            ->toBe(['module' => 'Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders', 'directory' => 'Actions']);
    });

    it('matches a pattern with no wildcard literally', function () {
        $pattern = new ModulePattern('Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders');

        expect($pattern->locate(PlaceOrder::class)['directory'])->toBe('Actions')
            ->and($pattern->locate(OrderHelper::class))->toBe(['module' => 'Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders', 'directory' => null])
            ->and($pattern->locate(OrderContract::class))->toBeNull();
    });

    it('matches a wildcard in the last segment only one segment deep', function () {
        $pattern = new ModulePattern('Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\*');

        expect($pattern->locate(ShipOrder::class))->toBe(['module' => 'Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop\Orders', 'directory' => 'Actions']);
    });

    it('treats a class whose namespace is the module itself as sitting directly in it', function () {
        expect((new ModulePattern(HARDENING_MODULES))->locate(OrderHelper::class)['directory'])->toBeNull();
    });

    it('leaves a class above every module alone', function () {
        expect((new ModulePattern(HARDENING_MODULES))->locate(OrderContract::class))->toBeNull();
    });

    it('matches the parent segments case-insensitively, as PHP namespaces are', function () {
        expect((new ModulePattern('dashworthy\pestpluginarchrails\tests\fixtures\domains\*\*'))->locate(PlaceOrder::class))->not->toBeNull();
    });

    it('rejects ** rather than silently matching nothing', function () {
        new ModulePattern('Dashworthy\PestPluginArchRails\Tests\Fixtures\**');
    })->throws(InvalidArgumentException::class, 'only * on its own, matching exactly one segment, is supported');

    it('rejects a partial wildcard segment', function () {
        new ModulePattern('App\Domains\Foo*');
    })->throws(InvalidArgumentException::class, "uses 'Foo*'");

    it('rejects an empty pattern or an empty segment', function (string $pattern) {
        new ModulePattern($pattern);
    })->throws(InvalidArgumentException::class)->with(['', '\\', 'App\\\\Domains']);
});

describe('ApprovedDirectoriesInspector', function () {
    it('explains a directory whose case differs from the approved name', function () {
        $inspect = new ApprovedDirectoriesInspector(['actions'], HARDENING_MODULES);

        expect($inspect(objectDescription(PlaceOrder::class)))->toContain("'Actions' differs only in case from approved 'actions'");
    });

    it('passes a class deeply nested in an approved directory with no type', function () {
        expect((new ApprovedDirectoriesInspector(['Actions'], HARDENING_MODULES))(objectDescription(ShipOrder::class)))->toBeNull();
    });

    it('holds a deeply nested class to its directory type', function () {
        $inspect = new ApprovedDirectoriesInspector(['Actions' => Action::class], HARDENING_MODULES);

        expect($inspect(objectDescription(ShipOrder::class)))->toContain('to be a '.Action::class)
            ->and($inspect(objectDescription(ActionAction::class)))->toBeNull()
            ->and($inspect(objectDescription(Action::class)))->toBeNull();
    });

    it('accepts an interface as the directory type', function () {
        $inspect = new ApprovedDirectoriesInspector(['Actions' => OrderContract::class], HARDENING_MODULES);

        expect($inspect(objectDescription(PlaceOrder::class)))->toBeNull()
            ->and($inspect(objectDescription(ActionAction::class)))->toContain('to be a '.OrderContract::class);
    });

    it('accepts UnitEnum and BackedEnum as the directory type', function (string $type) {
        expect((new ApprovedDirectoriesInspector(['Enums' => $type], HARDENING_MODULES))(objectDescription(OrderState::class)))->toBeNull();
    })->with([UnitEnum::class, BackedEnum::class]);

    it('names a directory type that does not exist as a configuration mistake', function () {
        new ApprovedDirectoriesInspector(['Actions' => 'App\DoesNotExist'], HARDENING_MODULES);
    })->throws(InvalidArgumentException::class, 'App\DoesNotExist, does not exist');

    it('rejects an empty directory name', function (array $directories) {
        new ApprovedDirectoriesInspector($directories, HARDENING_MODULES);
    })->throws(InvalidArgumentException::class)->with([[['']], [['' => Action::class]], [[42]]]);

    it('lists every approved directory when numeric and string keys are mixed', function () {
        $inspect = new ApprovedDirectoriesInspector(['Models', 'Actions' => Action::class, 'Enums'], HARDENING_MODULES);

        expect($inspect(objectDescription(OrderHelper::class)))->toContain('Use one of: Actions, Enums, Models.')
            ->and($inspect(objectDescription(OrderState::class)))->toBeNull();
    });

    it('reports every class in a module when no directory is approved', function () {
        $inspect = new ApprovedDirectoriesInspector([], HARDENING_MODULES);

        expect($inspect(objectDescription(PlaceOrder::class)))->toContain("'Actions' is not one. No directories are approved.");
    });

    it('says plainly that nothing is approved when the list is empty', function () {
        $inspect = new ApprovedDirectoriesInspector([], HARDENING_MODULES);

        expect($inspect(objectDescription(PlaceOrder::class)))->not->toContain('Use one of: .');
    });

    it('rejects a closure that returns a truthy non-bool', function (mixed $result) {
        $inspect = new ApprovedDirectoriesInspector(['Actions' => fn (string $class): mixed => $result], HARDENING_MODULES);

        expect($inspect(objectDescription(PlaceOrder::class)))->toContain('to pass the check');
    })->with([1, 'yes', [[true]], new stdClass]);

    it('reports a closure returning an empty reason without a dangling colon', function () {
        $inspect = new ApprovedDirectoriesInspector(['Actions' => fn (string $class): string => ''], HARDENING_MODULES);

        expect($inspect(objectDescription(PlaceOrder::class)))->toContain('but this one does not. Change');
    });

    it('reports an exception thrown by a closure as a violation naming the class', function () {
        $inspect = new ApprovedDirectoriesInspector(['Actions' => fn (string $class): bool => throw new RuntimeException('boom')], HARDENING_MODULES);

        expect($inspect(objectDescription(PlaceOrder::class)))
            ->toContain('the check threw RuntimeException for '.PlaceOrder::class.': boom');
    });

    it('does not crash on a closure given without a directory key', function () {
        new ApprovedDirectoriesInspector([fn (string $class): bool => true], HARDENING_MODULES);
    })->throws(InvalidArgumentException::class, 'has no directory name');

    it('leaves a class outside every module alone', function () {
        expect((new ApprovedDirectoriesInspector([], HARDENING_MODULES))(objectDescription(OrderContract::class)))->toBeNull();
    });
});

describe('DirectorySuffixesInspector', function () {
    it('passes a class already suffixed twice', function () {
        expect((new DirectorySuffixesInspector(['Actions' => 'Action'], HARDENING_MODULES))(objectDescription(ActionAction::class)))->toBeNull();
    });

    it('passes a class whose whole name is the suffix', function () {
        expect((new DirectorySuffixesInspector(['Actions' => 'Action'], HARDENING_MODULES))(objectDescription(Action::class)))->toBeNull();
    });

    it('reports a deeply nested class by its short name', function () {
        expect((new DirectorySuffixesInspector(['Actions' => 'Action'], HARDENING_MODULES))(objectDescription(ShipOrder::class)))
            ->toBe("Expecting every class in the Actions directory to end in 'Action', but ShipOrder does not. Rename it to end in 'Action', or move it to the directory for what it is.");
    });

    it('matches the suffix against the class name, not the namespace', function () {
        new DirectorySuffixesInspector(['Actions' => 'Still\ShipOrder'], HARDENING_MODULES);
    })->throws(InvalidArgumentException::class, 'matched against the short class name only');

    it('passes everything when given no suffixes', function () {
        expect((new DirectorySuffixesInspector([], HARDENING_MODULES))(objectDescription(PlaceOrder::class)))->toBeNull();
    });

    it('treats the directory name case-sensitively', function () {
        expect((new DirectorySuffixesInspector(['actions' => 'Action'], HARDENING_MODULES))(objectDescription(PlaceOrder::class)))->toBeNull();
    });

    it('rejects an empty suffix rather than passing every class', function () {
        new DirectorySuffixesInspector(['Actions' => ''], HARDENING_MODULES);
    })->throws(InvalidArgumentException::class, 'non-empty string');

    it('rejects a suffix not keyed by its directory', function () {
        new DirectorySuffixesInspector(['Action'], HARDENING_MODULES);
    })->throws(InvalidArgumentException::class, 'keyed by its directory name');
});

describe('arch chain', function () {
    it('accepts a deliberate namespace once rule-breakers are ignored', function () {
        expect('Dashworthy\PestPluginArchRails\Tests\Fixtures\Domains\Shop')
            ->toUseApprovedDirectories(['Actions', 'Enums'], beneath: HARDENING_MODULES)
            ->ignoring([OrderHelper::class]);
    });

    it('accepts the hardening fixtures under their suffixes', function () {
        expect(ActionAction::class)->toUseDirectorySuffixes(['Actions' => 'Action'], beneath: HARDENING_MODULES);
    });
});
