<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate;

class BypassedWithOrTrueRequest
{
    public function authorize(): bool
    {
        return Gate::allows('widgets_store') || true;
    }
}
