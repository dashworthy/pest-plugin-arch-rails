<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate;

class DoubleQuotedGateRequest
{
    public function authorize(): bool
    {
        return Gate::allows('widgets_store');
    }
}
