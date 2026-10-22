<?php

use Dashworthy\PestPluginArchRails\Core\LineFinder;
use Dashworthy\PestPluginArchRails\Core\MethodSource;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\AliasesTraitMethod;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\AttributedMethods;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\AttributeOnDeclarationLine;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\ChannelsTrait;
use PhpParser\Node\Stmt\Return_;

it('finds a method whose attribute or modifiers sit on earlier lines', function (string $method) {
    $source = MethodSource::find(new ReflectionClass(AttributedMethods::class), $method);

    expect($source)->not->toBeNull()
        ->and($source?->node->name->toString())->toBe($method)
        ->and($source?->nodesOfType(Return_::class))->toHaveCount(1);
})->with(['attribute on its own line' => '__toString', 'multiple attributes' => 'many']);

it('finds a method whose modifiers sit on separate lines', function () {
    require_once __DIR__.'/../Fixtures/Support/SplitModifiers.php.txt';

    /** @var class-string $class */
    $class = 'Dashworthy\\PestPluginArchRails\\Tests\\Fixtures\\Support\\SplitModifiers';
    $source = MethodSource::find(new ReflectionClass($class), 'split');

    expect($source?->node->name->toString())->toBe('split');
});

it('finds a trait method imported under an alias', function () {
    $source = MethodSource::find(new ReflectionClass(AliasesTraitMethod::class), 'via');

    expect($source)->not->toBeNull()
        ->and($source?->node->name->toString())->toBe('channels')
        ->and($source?->file)->toBe((new ReflectionClass(ChannelsTrait::class))->getFileName());
});

it('returns no nodes for a file with duplicate imports instead of crashing', function () {
    $code = (string) file_get_contents(__DIR__.'/../Fixtures/Support/DuplicateImports.php.txt');

    expect(MethodSource::parseCode($code))->toBe([]);
});

it('finds a declaration preceded by an attribute on the same line', function () {
    $path = (string) (new ReflectionClass(AttributeOnDeclarationLine::class))->getFileName();

    expect(LineFinder::declaration()($path))->toBe(7);
});
