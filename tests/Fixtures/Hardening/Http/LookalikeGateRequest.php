<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

class LookalikeGateRequest
{
    public function authorize(): bool
    {
        return NotAGate::allows('widgets_store');
    }
}
