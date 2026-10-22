<?php

use Dashworthy\PestPluginArchRails\Core\LineFinder;
use Dashworthy\PestPluginArchRails\Tests\Fixtures\Support\AlwaysFails;

it('finds the line containing the needle', function () {
    $path = (string) (new ReflectionClass(AlwaysFails::class))->getFileName();

    expect(LineFinder::methodOrClass('find-me')($path))->toBe(9);
});

it('falls back to the class line when the needle is missing', function () {
    $path = (string) (new ReflectionClass(AlwaysFails::class))->getFileName();

    expect(LineFinder::methodOrClass('function missing')($path))->toBe(7);
});
