<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate;

class DottedAbilityRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin:widgets.store');
    }
}
