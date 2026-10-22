<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate;

class ReturnsTrueAfterGateRequest
{
    public function authorize(): bool
    {
        Gate::allows('widgets_store');

        return true;
    }
}
