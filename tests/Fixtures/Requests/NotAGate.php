<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

class NotAGate
{
    public static function allows(string $ability): bool
    {
        return true;
    }
}
