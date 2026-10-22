<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

class NotAGate
{
    public static function allows(string $ability): bool
    {
        return true;
    }
}
