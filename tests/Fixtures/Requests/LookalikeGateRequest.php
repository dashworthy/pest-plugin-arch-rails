<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

class LookalikeGateRequest
{
    public function authorize(): bool
    {
        return NotAGate::allows('widgets_store');
    }
}
